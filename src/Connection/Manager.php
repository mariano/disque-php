<?php
namespace Disque\Connection;

use InvalidArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Command\Hello;
use Disque\Connection\Exception\ConnectionException;

class Manager implements ManagerInterface
{
    /**
     * List of servers
     *
     * @var array
     */
    protected $servers = [];

    /**
     * Connection options
     *
     * @var array
     */
    protected $options = [];

    /**
     * If a node has produced at least these number of jobs, switch there
     *
     * @var int
     */
    protected $minimumJobsToChangeNode = 0;

    /**
     * List of nodes. Indexed by node ID, and as value an array with:
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
     * Get the connection implementation class
     *
     * @return string A fully classified class name that implements `ConnectionInterface`
     */
    public function getConnectionClass()
    {
        return $this->connectionClass;
    }

    /**
     * Set the connection implementation class
     *
     * @param string $class A fully classified class name that must implement `ConnectionInterface`
     * @return void
     * @throws InvalidArgumentException
     */
    public function setConnectionClass($class)
    {
        if (!in_array(ConnectionInterface::class, class_implements($class))) {
            throw new InvalidArgumentException("Class {$class} does not implement ConnectionInterface");
        }
        $this->connectionClass = $class;
    }

    /**
     * Get available servers
     *
     * @return array Each server is an indexed array with `host` and `port`
     */
    public function getServers()
    {
        return $this->servers;
    }

    /**
     * Add a new server
     *
     * @param string $host Host
     * @param int $port Port
     * @return void
     * @throws InvalidArgumentException
     */
    public function addServer($host, $port = 7711)
    {
        if (!is_string($host) || !is_int($port)) {
            throw new InvalidArgumentException('Invalid server specified');
        }

        $port = (int) $port;
        $this->servers[] = compact('host', 'port');
    }

    /**
     * Set connection options sent to the connector's `connect` method
     *
     * @param array $options Connection options
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * If a node has produced at least these number of jobs, switch there
     *
     * @param int $minimumJobsToChangeNode Set to 0 to never change
     * @return void
     */
    public function setMinimumJobsToChangeNode($minimumJobsToChangeNode)
    {
        $this->minimumJobsToChangeNode = $minimumJobsToChangeNode;
    }

    /**
     * Connect to Disque
     *
     * @return array Connected node information
     * @throws ConnectionException
     */
    public function connect()
    {
        $result = $this->findAvailableConnection();
        if (!isset($result['connection'])) {
            throw new ConnectionException('No servers available');
        } elseif (empty($result['hello']) || empty($result['hello']['nodes']) || empty($result['hello']['id'])) {
            throw new ConnectionException('Invalid HELLO response when connecting');
        }

        $hello = $result['hello'];
        $connection = $result['connection'];

        $this->nodes = [];
        $this->nodeId = $hello['id'];
        foreach ($hello['nodes'] as $node) {
            $this->nodePrefixes[substr($node['id'], 2, 8)] = $node['id'];
            $this->nodes[$node['id']] = [
                'connection' => ($node['id'] === $this->nodeId ? $connection : null),
                'port' => (int) $node['port'],
                'jobs' => 0
            ] + array_intersect_key($node, ['id'=>null, 'host'=>null, 'version'=>null]);
        }

        if (!array_key_exists($hello['id'], $this->nodes)) {
            throw new ConnectionException("Connected node #{$hello['id']} could not be found in list of nodes");
        }

        return $hello;
    }

    /**
     * Execute the given command on the given connection
     *
     * @param CommandInterface $command Command
     * @return mixed Command response
     */
    public function execute(CommandInterface $command)
    {
        if (
            !isset($this->nodes[$this->nodeId]['connection']) ||
            !$this->nodes[$this->nodeId]['connection']->isConnected()
        ) {
            throw new ConnectionException('Not connected');
        }

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
     * @throws ConnectionException
     */
    protected function findAvailableConnection()
    {
        if (empty($this->servers)) {
            throw new ConnectionException('No servers specified');
        }

        $node = [];
        $servers = $this->servers;
        while (!empty($servers)) {
            $key = array_rand($servers, 1);
            $server = $servers[$key];
            $node = $this->getNodeConnection($server);
            if (isset($node['connection'])) {
                break;
            }
            unset($servers[$key]);
        }
        if (!isset($node['connection'])) {
            throw new ConnectionException('No servers available');
        }
        return [
            'connection' => $node['connection'],
            'hello' => $node['hello']
        ];
    }

    /**
     * Get a node connection and its HELLO result
     *
     * @param array $server Server (with `host`, and `port`)
     * @return array Indexed array with `connection` and `hello`. `connection`
     * could end up being null
     */
    protected function getNodeConnection(array $server)
    {
        $helloCommand = new Hello();
        $connection = $this->buildConnection($server['host'], $server['port']);
        $hello = [];
        try {
            $connection->connect($this->options);
            $hello = $helloCommand->parse($connection->execute($helloCommand));
        } catch (ConnectionException $e) {
            $connection = null;
            $hello = [];
        }
        return compact('connection', 'hello');
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
            $nodePrefix = substr($job['id'], 2, 8);
            if (
                !isset($this->nodePrefixes[$nodePrefix]) ||
                !array_key_exists($this->nodePrefixes[$nodePrefix], $this->nodes)
            ) {
                continue;
            }

            $nodeId = $this->nodePrefixes[$nodePrefix];
            $this->nodes[$nodeId]['jobs']++;
            if ($this->nodes[$nodeId]['jobs'] >= $this->minimumJobsToChangeNode) {
                $newNodeId = $nodeId;
                break;
            }
        }

        if (!isset($newNodeId) || $newNodeId === $this->nodeId) {
            return;
        }

        $this->setNode($newNodeId);
        foreach ($this->nodes as $id => $node) {
            $this->nodes[$id]['jobs'] = 0;
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
        if (!isset($this->nodes[$id]['connection'])) {
            $node = $this->getNodeConnection($this->nodes[$id]);
            if (!isset($node['connection'])) {
                throw new ConnectionException("Could not connect to node {$id}");
            }
            $this->nodes[$id]['connection'] = $node['connection'];
        }

        $this->nodeId = $id;
    }
}