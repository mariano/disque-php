<?php
namespace Disque\Connection;

use Disque\Command\Auth;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Command\Hello;
use Disque\Connection\ConnectionException;
use InvalidArgumentException;

class Manager implements ManagerInterface
{
    /**
     * List of servers (or initial nodes) that we can connect to
     *
     * After connecting to one, the server returns a list of other nodes
     * in the cluster.
     *
     * @var array
     */
    protected $servers = [];

    /**
     * If a node has produced at least these number of jobs, switch there
     *
     * @var int
     */
    protected $minimumJobsToChangeNode = 0;

    /**
     * List of nodes, ie Disque instances available in the cluster
     *
     * Indexed by node ID, and as value an array with:
     * - ConnectionInterface|null `connection`
     * - string `host`
     * - int `port`
     * - string `version`
     *
     * @var array
     */
    protected $nodes = [];

    /**
     * Node prefixes, and their corresponding node ID
     *
     * Node prefix consists of the first 8 bytes from the node ID. Because job
     * IDs contain the node prefix, it can be used to identify on which node
     * a job lives.
     *
     * @var array
     */
    protected $nodePrefixes = [];

    /**
     * Current node ID we are connected to
     *
     * @var string
     */
    protected $nodeId;

    /**
     * Connection implementation
     *
     * @var string
     */
    protected $connectionClass = Socket::class;

    /**
     * @inheritdoc
     */
    public function getConnectionClass()
    {
        return $this->connectionClass;
    }

    /**
     * @inheritdoc
     */
    public function setConnectionClass($class)
    {
        if (!in_array(ConnectionInterface::class, class_implements($class))) {
            throw new InvalidArgumentException("Class {$class} does not implement ConnectionInterface");
        }
        $this->connectionClass = $class;
    }

    /**
     * @inheritdoc
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * @inheritdoc
     */
    public function addServer($host, $port = 7711, $password = null, array $options = [])
    {
        if (!is_string($host) || !is_int($port)) {
            throw new InvalidArgumentException('Invalid server specified');
        }

        $port = (int) $port;
        $this->servers[] = compact('host', 'options', 'port', 'password');
    }

    /**
     * @inheritdoc
     */
    public function setMinimumJobsToChangeNode($minimumJobsToChangeNode)
    {
        $this->minimumJobsToChangeNode = $minimumJobsToChangeNode;
    }

    /**
     * @inheritdoc
     */
    public function isConnected()
    {
        return (
            isset($this->nodeId) &&
            isset($this->nodes[$this->nodeId]['connection']) &&
            $this->nodes[$this->nodeId]['connection']->isConnected()
        );
    }

    /**
     * @inheritdoc
     */
    public function connect()
    {
        $result = $this->findAvailableConnection();
        if (!isset($result['connection'])) {
            throw new ConnectionException('No servers available');
        }

        $hello = $result['hello'];
        $this->nodes = [];
        $this->nodeId = $hello['id'];
        foreach ($hello['nodes'] as $node) {
            $this->nodePrefixes[substr($node['id'], 2, 8)] = $node['id'];
            $this->nodes[$node['id']] = [
                'connection' => ($node['id'] === $this->nodeId ? $result['connection'] : null),
                'port' => (int) $node['port'],
                'jobs' => 0
            ] + array_intersect_key($node, ['id'=>null, 'host'=>null, 'version'=>null]);
        }

        return $hello;
    }

    /**
     * @inheritdoc
     */
    public function execute(CommandInterface $command)
    {
        $this->shouldBeConnected();
        $response = $this->nodes[$this->nodeId]['connection']->execute($command);
        if ($command instanceof GetJob && $this->minimumJobsToChangeNode > 0) {
            try {
                $this->changeNodeIfNeeded($command->parse($response));
            } catch (ConnectionException $e) {
                // If we couldn't, let's stay with the current node
            }
        }
        return $response;
    }

    /**
     * Get connection
     *
     * @return array Indexed array with `connection` and `hello`. `connection`
     * could end up being null
     * @throws AuthenticationException
     * @throws ConnectionException
     */
    protected function findAvailableConnection()
    {
        $servers = $this->servers;
        while (!empty($servers)) {
            $key = array_rand($servers, 1);
            $server = $servers[$key];
            $node = $this->getNodeConnection($server);
            if (isset($node['connection'])) {
                return array_intersect_key($node, ['connection'=>null, 'hello'=>null]);
            }
            unset($servers[$key]);
        }
        throw new ConnectionException('No servers available');
    }

    /**
     * Get a node connection and its HELLO result
     *
     * @param array $server Server (with `host`, `options`, `port`, and `password`)
     * @return array Indexed array with `connection` and `hello`. `connection`
     * could end up being null
     * @throws AuthenticationException
     */
    protected function getNodeConnection(array $server)
    {
        $helloCommand = new Hello();
        $connection = $this->buildConnection($server['host'], $server['port']);
        $hello = [];
        try {
            $this->doConnect($connection, $server);
            $hello = $helloCommand->parse($connection->execute($helloCommand));
        } catch (ConnectionException $e) {
            $message = $e->getMessage();
            if (stripos($message, 'NOAUTH') === 0) {
                throw new AuthenticationException($message);
            }
            $connection = null;
            $hello = [];
        }
        return compact('connection', 'hello');
    }

    /**
     * Actually perform the connection
     *
     * @param ConnectionInterface $connection Connection
     * @param array $server Server (with `host`, `options', `port`, and `password`)
     *
     * @throws AuthenticationException
     */
    private function doConnect(ConnectionInterface $connection, array $server)
    {
        $server += ['options' => []];
        $connection->connect($server['options']);
        if (!empty($server['password'])) {
            $authCommand = new Auth();
            $authCommand->setArguments([$server['password']]);
            $response = $authCommand->parse($connection->execute($authCommand));
            if ($response !== 'OK') {
                throw new AuthenticationException();
            }
        }
    }

    /**
     * Build a new connection
     *
     * @param string $host Host
     * @param int $port Port
     * @return ConnectionInterface
     */
    protected function buildConnection($host, $port)
    {
        $connectionClass = $this->connectionClass;
        $connection = new $connectionClass();
        $connection->setHost($host);
        $connection->setPort($port);
        return $connection;
    }

    /**
     * Decide if we should change the current node based on the jobs returned.
     * If so, attempt to switch to that node
     *
     * @param array $jobs Jobs
     * @throws ConnectionException
     */
    private function changeNodeIfNeeded(array $jobs)
    {
        foreach ($jobs as $job) {
            $nodeId = $this->getNodeIdFromJobId($job['id']);
            if (!isset($nodeId)) {
                continue;
            }
            $this->nodes[$nodeId]['jobs']++;
            if ($this->nodes[$nodeId]['jobs'] >= $this->minimumJobsToChangeNode) {
                $this->setNode($nodeId);
                return;
            }
        }
    }

    /**
     * Choose this node for future connections
     *
     * @param string $id Node ID
     * @throws ConnectionException
     */
    private function setNode($id)
    {
        if ($id === $this->nodeId) {
            return;
        }

        $this->loadNodeConnection($id);
        $this->nodeId = $id;
        foreach ($this->nodes as $id => $node) {
            $this->nodes[$id]['jobs'] = 0;
        }
    }

    /**
     * Ensure a connection is made to the given node ID
     *
     * @param string $id Node ID
     * @return void
     * @throws ConnectionException
     */
    private function loadNodeConnection($id)
    {
        $node = $this->getNodeConnection($this->nodes[$id]);
        if (!isset($node['connection'])) {
            throw new ConnectionException("Could not connect to node {$id}");
        }
        $this->nodes[$id]['connection'] = $node['connection'];
    }

    /**
     * Get node ID based off a Job ID
     *
     * @param string $jobId Job ID
     * @return string|null Node ID
     */
    private function getNodeIdFromJobId($jobId)
    {
        $nodePrefix = substr($jobId, 2, 8);
        if (
            !isset($this->nodePrefixes[$nodePrefix]) ||
            !array_key_exists($this->nodePrefixes[$nodePrefix], $this->nodes)
        ) {
            return null;
        }

        return $this->nodePrefixes[$nodePrefix];
    }

    /**
     * We should be connected
     *
     * @return void
     * @throws ConnectionException
     */
    private function shouldBeConnected()
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('Not connected');
        }
    }
}
