<?php
namespace Disque\Connection;

use InvalidArgumentException;
use Disque\Command\CommandInterface;
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
     * @return string A fully classified class name that implements `Disque\Connection\ConnectionInterface`
     */
    public function getConnectionClass()
    {
        return $this->connectionClass;
    }

    /**
     * Set the connection implementation class
     *
     * @param string $class A fully classified class name that must implement ConnectionInterface
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
     * Connect to Disque
     *
     * @param array $options Connection options
     * @return array Connected node information
     * @throws ConnectionException
     */
    public function connect(array $options)
    {
        $result = $this->findAvailableConnection($options);
        if (!isset($result['connection'])) {
            throw new ConnectionException('No servers available');
        } elseif (empty($result['hello']) || empty($result['hello']['nodes']) || empty($result['hello']['id'])) {
            throw new ConnectionException('Invalid HELLO response when connecting');
        }

        $hello = $result['hello'];
        $connection = $result['connection'];

        $nodes = [];
        foreach ($hello['nodes'] as $node) {
            $nodes[$node['id']] = [
                'connection' => null,
                'port' => (int) $node['port'],
            ] + array_intersect_key($node, ['id'=>null, 'host'=>null, 'version'=>null]);
        }

        if (!array_key_exists($hello['id'], $nodes)) {
            throw new ConnectionException("Connected node #{$hello['id']} could not be found in list of nodes");
        }

        $nodes[$hello['id']]['connection'] = $connection;

        $this->nodeId = $hello['id'];
        $this->nodes = $nodes;
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
        if (empty($this->nodes) || !isset($this->nodeId) || !isset($this->nodes[$this->nodeId]['connection'])) {
            throw new ConnectionException('Not connected');
        }

        $connection = $this->nodes[$this->nodeId]['connection'];
        return $this->command($connection, $command);
    }

    /**
     * Execute the given command on the given connection
     *
     * @param ConnectionInterface $connection Connection
     * @param CommandInterface $command Command
     * @return mixed Command response
     */
    protected function command(ConnectionInterface $connection, CommandInterface $command)
    {
        return $connection->execute($command);
    }

    /**
     * Get connection
     *
     * @param array $options Connection options
     * @return array Indexed array with `connection` and `hello`. `connection`
     * could end up being null
     * @throws ConnectionException
     */
    protected function findAvailableConnection(array $options)
    {
        if (empty($this->servers)) {
            throw new ConnectionException('No servers specified');
        }

        $servers = $this->servers;
        $connection = null;
        $hello = [];
        $helloCommand = new Hello();
        while (!empty($servers)) {
            $key = array_rand($servers, 1);
            $server = $servers[$key];
            $connection = $this->buildConnection($server['host'], $server['port']);
            try {
                $connection->connect($options);
                $hello = $helloCommand->parse($this->command($connection, $helloCommand));
                break;
            } catch (ConnectionException $e) {
                unset($servers[$key]);
                $connection = null;
                continue;
            }
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
}