<?php
namespace Disque\Connection;

use Disque\Connection\Node\Node;
use Disque\Connection\Node\NodePrioritizerInterface;
use Disque\Connection\Node\ConservativeJobCountPrioritizer;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Command\Response\HelloResponse;
use Disque\Command\Response\JobsResponse;
use Disque\Connection\Factory\ConnectionFactoryInterface;
use Disque\Connection\Factory\SocketFactory;

/**
 * The connection manager connects to Disque nodes and chooses the best of them
 *
 * If there are multiple nodes to connect, the first connection is always
 * random. The manager then switches to the best nodes according to its
 * NodePriority strategy.
 *
 * If the manager knows the credentials of only one node, it will automatically
 * discover other nodes in the cluster and connect to them if needed (unless
 * they are password-protected).
 */
class Manager implements ManagerInterface
{
    /**
     * Servers we can connect to initially, without knowing the cluster
     *
     * After connecting to one, the server returns a list of other nodes
     * in the cluster so we can connect to them automatically, unless
     * the discovered nodes are secured with a password.
     *
     * 'serverAddress' => Credentials
     *
     * @var Credentials[]
     */
    protected $credentials = [];

    /**
     * A strategy to prioritize nodes and find the best one to switch to
     *
     * The default strategy is the ConservativeJobCountPrioritizer. It
     * prioritizes nodes by their job count, but prefers the current node
     * in order to avoid switching until there is a clearly better node.
     *
     * @var NodePrioritizerInterface
     */
    protected $priorityStrategy;

    /**
     * List of nodes, ie Disque instances available in the cluster
     *
     * 'nodeId' => Node
     *
     * @var Node[]
     */
    protected $nodes = [];

    /**
     * Node prefixes and their corresponding node ID
     *
     * Node prefix consists of the first 8 bytes from the node ID. Because job
     * IDs contain the node prefix, it can be used to identify on which node
     * a job lives.
     *
     * 'nodePrefix' => 'nodeId'
     *
     * @var array
     */
    protected $nodePrefixes = [];

    /**
     * The ID of the node we are currently connected to
     *
     * @var string
     */
    protected $nodeId;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct()
    {
        $this->connectionFactory = new SocketFactory();
        $this->priorityStrategy = new ConservativeJobCountPrioritizer();
    }

    /**
     * @inheritdoc
     */
    public function getConnectionFactory()
    {
        return $this->connectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function setConnectionFactory(
        ConnectionFactoryInterface $connectionFactory
    ) {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @inheritdoc
     */
    public function addServer(Credentials $credentials)
    {
        $address = $credentials->getAddress();
        $this->credentials[$address] = $credentials;
    }

    /**
     * @inheritdoc
     */
    public function getPriorityStrategy()
    {
        return $this->priorityStrategy;
    }

    /**
     * @inheritdoc
     */
    public function setPriorityStrategy(NodePrioritizerInterface $priorityStrategy)
    {
        $this->priorityStrategy = $priorityStrategy;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return (
            isset($this->nodeId) &&
            $this->getCurrentNode()->isConnected()
        );
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        // If the manager was already connected, connect to a node from the last
        // HELLO response. This information is newer than the credentials
        // supplied by the user at the beginning.
        if ($this->wasAlreadyConnected()) {
            try {
                $this->switchNodeIfNeeded();
            } catch (ConnectionException $e) {
                // Ignore the error, we'll try reconnecting with credentials below
            }
        }

        // Use the user-supplied credentials in case this is the initial
        // connection.
        // If the reconnection attempt above didn't work, fall back
        // to the user-supplied credentials, too.
        if ( ! $this->isConnected()) {
            $currentNode = $this->findAvailableConnection();
            $this->switchToNode($currentNode);
        }

        return $this->getCurrentNode();
    }

    /**
     * @inheritdoc
     */
    public function execute(CommandInterface $command)
    {
        $this->shouldBeConnected();
        $command = $this->preprocessExecution($command);

        $response = $this->getCurrentNode()->execute($command);

        $response = $this->postprocessExecution($command, $response);
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentNode()
    {
        return $this->nodes[$this->nodeId];
    }

    /**
     * Get a functional connection to any known node
     *
     * Disque suggests the first connection should be chosen randomly
     * We go through the user-supplied credentials randomly and try to connect.
     *
     * @return Node A connected node
     *
     * @throws ConnectionException
     */
    protected function findAvailableConnection()
    {
        $servers = $this->credentials;
        shuffle($servers);
        $previous = null;

        foreach ($servers as $server) {
            try {
                $node = $this->getNodeConnection($server);
            } catch (ConnectionException $e) {
                $previous = $e;
                continue;
            }

            if ($node->isConnected()) {
                return $node;
            }
        }

        throw new ConnectionException('No servers available', 0, $previous);
    }

    /**
     * Connect to the node given in the credentials
     *
     * @param Credentials $server
     *
     * @return Node A connected node
     *
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    protected function getNodeConnection(Credentials $server)
    {
        $node = $this->createNode($server);
        $node->connect();
        return $node;
    }

    /**
     * Reset node counters that should be reset upon node switch
     */
    protected function resetNodeCounters()
    {
        foreach($this->nodes as $node) {
            $node->resetJobCount();
        }
    }

    /**
     * Hook into the command execution and do anything before it's executed
     *
     * Eg. start measuring node latency etc.
     *
     * @param CommandInterface $command
     *
     * @return CommandInterface $command
     */
    protected function preprocessExecution(CommandInterface $command)
    {
        return $command;
    }

    /**
     * Postprocess the command execution, eg. update node stats
     *
     * @param CommandInterface $command
     * @param mixed            $response
     *
     * @return mixed
     * @throws ConnectionException
     */
    protected function postprocessExecution(
        CommandInterface $command,
        $response
    ) {
        if ($command instanceof GetJob) {
            $this->updateNodeStats($command->parse($response));
            $this->switchNodeIfNeeded();
        }

        return $response;
    }

    /**
     * Update node counters indicating how many jobs the node has produced
     *
     * @param array $jobs Jobs
     */
    protected function updateNodeStats(array $jobs)
    {
        foreach ($jobs as $job) {
            $jobId = $job[JobsResponse::KEY_ID];
            $nodeId = $this->getNodeIdFromJobId($jobId);
            if (!isset($nodeId) || !isset($this->nodes[$nodeId])) {
                continue;
            }

            $node = $this->nodes[$nodeId];
            $node->addJobCount(1);
        }
    }

    /**
     * Decide if we should switch to a better node
     *
     * @throws ConnectionException
     */
    private function switchNodeIfNeeded()
    {
        $sortedNodes = $this->priorityStrategy->sort(
            $this->nodes,
            $this->nodeId
        );

        $previous = null;

        // Try to connect by priority, continue on error, return on success
        foreach($sortedNodes as $nodeCandidate) {
            // If the first recommended node is our current node and it has
            // a working connection, return early.
            // If its connection is not working, let's try and reconnect further
            // below, or find the first best connected node.
            if ($nodeCandidate->getId() === $this->nodeId &&
                $nodeCandidate->isConnected()) {
                return;
            }

            try {
                if ($nodeCandidate->isConnected()) {
                    // Say a new HELLO to the node, the cluster might have changed
                    $nodeCandidate->sayHello();
                } else {
                    $nodeCandidate->connect();
                }
            } catch (ConnectionException $e) {
                $previous = $e;
                continue;
            }

            $this->switchToNode($nodeCandidate);
            return;
        }

        throw new ConnectionException('Could not switch to any node', 0, $previous);
    }

    /**
     * Get a node ID based off a Job ID
     *
     * @param string       $jobId Job ID
     * @return string|null        Node ID
     */
    private function getNodeIdFromJobId($jobId)
    {
        $nodePrefix = $this->getNodePrefixFromJobId($jobId);
        if (
            isset($this->nodePrefixes[$nodePrefix]) &&
            array_key_exists($this->nodePrefixes[$nodePrefix], $this->nodes)
        ) {
            return $this->nodePrefixes[$nodePrefix];
        }

        return null;
    }

    /**
     * Get the node prefix from the job ID
     *
     * @param string  $jobId
     * @return string        Node prefix
     */
    private function getNodePrefixFromJobId($jobId)
    {
        $nodePrefix = substr(
            $jobId,
            JobsResponse::ID_NODE_PREFIX_START,
            Node::PREFIX_LENGTH
        );

        return $nodePrefix;
    }

    /**
     * We should be connected
     *
     * @return void
     * @throws ConnectionException
     */
    protected function shouldBeConnected()
    {
        // If we lost the connection, first let's try and reconnect
        if (!$this->isConnected()) {
            try {
                $this->switchNodeIfNeeded();
            } catch (ConnectionException $e) {
                throw new ConnectionException('Not connected. ' . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Create a new Node object
     *
     * @param Credentials $credentials
     *
     * @return Node An unconnected Node
     */
    private function createNode(Credentials $credentials)
    {
        $host = $credentials->getHost();
        $port = $credentials->getPort();
        $connection = $this->connectionFactory->create($host, $port);

        return new Node($credentials, $connection);
    }

    /**
     * Switch to the given node and map the cluster from its HELLO
     *
     * @param Node $node
     */
    private function switchToNode(Node $node)
    {
        $nodeId = $node->getId();
        // Return early if we're trying to switch to the current node.
        if (($this->nodeId === $nodeId)) {
            // But return early only if the current node is connected to Disque.
            // If it is disconnected, we want to overwrite it with the node
            // from the method argument, because that one is connected.
            if ($this->getCurrentNode()->isConnected()) {
                return;
            }

            // Copy the stats from the now-disconnected node object
            $this->copyNodeStats($this->getCurrentNode(), $node);
        }

        $this->resetNodeCounters();

        $this->nodeId = $nodeId;
        $this->nodes[$nodeId] = $node;
        $this->revealClusterFromHello($node);
    }

    /**
     * Reveal the whole Disque cluster from a node HELLO response
     *
     * The HELLO response from a Disque node contains addresses of all other
     * nodes in the cluster. We want to learn about them and save them, so that
     * we can switch to them later, if needed. Nodes in the HELLO response without
     * a hostname are not used.
     *
     * @param Node $node The current node
     */
    private function revealClusterFromHello(Node $node)
    {
        $hello = $node->getHello();
        $revealedNodes = [];

        foreach ($hello[HelloResponse::NODES] as $node) {
            if (!$node[HelloResponse::NODE_HOST]) {
                continue;
            }

            $id = $node[HelloResponse::NODE_ID];
            $revealedNode = $this->revealNodeFromHello($id, $node);

            // Update or set the node's priority as determined by Disque
            $priority = $node[HelloResponse::NODE_PRIORITY];
            $revealedNode->setPriority($priority);

            $revealedNodes[$id] = $revealedNode;
        }

        $this->nodes = $revealedNodes;
    }

    /**
     * Reveal a single node from a HELLO response, or use an existing node
     *
     * @param string $nodeId The node ID
     * @param array  $node   Node information as returned by the HELLO command
     *
     * @return Node $node A node in the current cluster
     */
    private function revealNodeFromHello($nodeId, array $node)
    {
        /**
         * Add the node prefix to the pool. We create the prefix manually
         * from the node ID rather than asking the Node object. Newly created
         * Nodes aren't connected and thus don't know their ID or prefix.
         *
         * @see Node::sayHello()
         */
        $prefix = substr($nodeId, Node::PREFIX_START, Node::PREFIX_LENGTH);
        $this->nodePrefixes[$prefix] = $nodeId;

        // If the node already exists in our pool, use it, don't overwrite it
        // with a new one. We would lose its stats and connection.
        if (isset($this->nodes[$nodeId])) {
            return $this->nodes[$nodeId];
        }

        $host = $node[HelloResponse::NODE_HOST];
        $port = $node[HelloResponse::NODE_PORT];
        $credentials = new Credentials($host, $port);

        $address = $credentials->getAddress();
        // If there are user-supplied credentials for this node, use them.
        // They may contain a password
        if (isset($this->credentials[$address])) {
            $credentials = $this->credentials[$address];
        }

        // Instantiate a new Node object for the newly revealed node
        return $this->createNode($credentials);
    }

    /**
     * Check if the manager held a connection to Disque already
     *
     * @return bool
     */
    private function wasAlreadyConnected()
    {
        return ( ! empty($this->nodes));
    }

    /**
     * Copy node stats from the old to the new node
     *
     * @param Node $oldNode
     * @param Node $newNode
     */
    private function copyNodeStats(Node $oldNode, Node $newNode)
    {
        $oldNodeJobCount = $oldNode->getTotalJobCount();
        $newNode->addJobCount($oldNodeJobCount);
    }
}
