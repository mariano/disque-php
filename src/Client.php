<?php
namespace Disque;

use InvalidArgumentException;
use Disque\Command;
use Disque\Connection\Exception\ConnectionException;
use Disque\Connection\Connection;
use Disque\Connection\ConnectionInterface;
use Disque\Exception\InvalidCommandException;

/**
 * @method int ackjob(string... $ids)
 * @method string addjob(array $job)
 * @method int deljob(string... $ids)
 * @method int dequeue(string... $ids)
 * @method int enqueue(string... $ids)
 * @method int fastack(string... $ids)
 * @method array getjob(string... $queues, array $options)
 * @method array hello()
 * @method string info()
 * @method int qlen(string $queue)
 * @method array qpeek(string $queue, int $count)
 * @method array show(string $id)
 */
class Client
{
    /**
     * List of servers
     *
     * @var array
     */
    protected $servers = [];

    /**
     * List of nodes. Indexed by node ID, and as value an array with:
     * - Disque\Connection\ConnectionInterface|null `connection`
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
     * Command handlers
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Connection implementation class
     *
     * @var string
     */
    protected $connectionImplementation;

    /**
     * Create a new Client
     *
     * @param array $servers Servers
     */
    public function __construct(array $servers = [['host' => '127.0.0.1', 'port' => 7711]])
    {
        $this->setConnectionImplementation(Connection::class);
        foreach ($servers as $server) {
            $this->addServer($server['host'], $server['port']);
        }

        foreach ([
            'ACKJOB' => Command\AckJob::class,
            'ADDJOB' => Command\AddJob::class,
            'DELJOB' => Command\DelJob::class,
            'DEQUEUE' => Command\Dequeue::class,
            'ENQUEUE' => Command\Enqueue::class,
            'FASTACK' => Command\FastAck::class,
            'GETJOB' => Command\GetJob::class,
            'HELLO' => Command\Hello::class,
            'INFO' => Command\Info::class,
            'QLEN' => Command\QLen::class,
            'QPEEK' => Command\QPeek::class,
            'SHOW' => Command\Show::class
        ] as $command => $handlerClass) {
            $this->registerCommand($command, new $handlerClass());
        }
    }

    /**
     * Set the connection implementation class
     *
     * @param string $class A fully classified class name that must implement
     * Disque\Connection\ConnectionInterface
     * @throws InvalidArgumentException
     */
    public function setConnectionImplementation($class)
    {
        if (!in_array(ConnectionInterface::class, class_implements($class))) {
            throw new InvalidArgumentException("Class {$class} does not implement ConnectionInterface");
        }
        $this->connectionImplementation = $class;
    }

    /**
     * Add a new server
     *
     * @param string $host Host
     * @param int $port Port
     * @throws InvalidArgumentException
     */
    public function addServer($host, $port = 7711)
    {
        if (!is_string($host) || !is_int($port)) {
            throw new InvalidArgumentException('Invalid server specified');
        }

        $this->servers[] = compact('host', 'port');
    }

    /**
     * Connect to Disque
     *
     * @param array $options Connection options
     * @return array Connected node information
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function connect(array $options = [])
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
     * Get current connection
     *
     * @return Disque\Connection\ConnectionInterface
     * @throws Disque\Connection\Exception\ConnectionException
     */
    protected function getConnection()
    {
        if (empty($this->nodes) || !isset($this->nodeId) || !isset($this->nodes[$this->nodeId]['connection'])) {
            throw new ConnectionException('Not connected');
        }
        return $this->nodes[$this->nodeId]['connection'];
    }

    /**
     * Get connection
     *
     * @param array $options Connection options
     * @return array Indexed array with `connection` and `hello`. `connection`
     * could end up being null
     */
    protected function findAvailableConnection(array $options)
    {
        $servers = $this->servers;
        $connection = null;
        $hello = [];
        while (!empty($servers)) {
            $key = array_rand($servers, 1);
            $server = $servers[$key];
            $connection = $this->buildConnection($server['host'], $server['port']);
            try {
                $connection->connect($options);
                $hello = $this->execute($connection, $this->commands['HELLO']);
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
     * @return Disque\Connection\ConnectionInterface
     */
    protected function buildConnection($host, $port)
    {
        $connectionClass = $this->connectionImplementation;
        $connection = new $connectionClass();
        $connection->setHost($host);
        $connection->setPort($port);
        return $connection;
    }

    /**
     * Register a command handler
     *
     * @param string $command Command
     * @param Disque\Command\CommandInterface $handler Command handler
     */
    public function registerCommand($command, Command\CommandInterface $handler)
    {
        $this->commands[mb_strtoupper($command)] = $handler;
    }

    /**
     * @throws Disque\Exception\InvalidCommandException
     */
    public function __call($command, array $arguments)
    {
        $command = mb_strtoupper($command);
        if (!isset($this->commands[$command])) {
            throw new InvalidCommandException($command);
        }
        return $this->execute($this->getConnection(), $this->commands[$command], $arguments);
    }

    /**
     * Execute the given command on the given connection
     *
     * @param ConnectionInterface $connection Connection
     * @param Disque\Command\CommandInterface $handler Command handler
     * @param array $arguments Arguments for command
     * @return mixed Command response
     */
    protected function execute(ConnectionInterface $connection, Command\CommandInterface $command, array $arguments = [])
    {
        $response = $connection->execute($command, $arguments);
        return $command->parse($response);
    }
}