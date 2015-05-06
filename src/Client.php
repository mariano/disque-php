<?php
namespace Disque;

use Disque\Command;
use Disque\Connection\Connection;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Exception\ConnectionException;
use Disque\Exception\InvalidCommandException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
    const LOG_EMERGENCY = 0;
    const LOG_ALERT = 1;
    const LOG_CRITICAL = 2;
    const LOG_ERROR = 3;
    const LOG_WARNING = 4;
    const LOG_NOTICE = 5;
    const LOG_INFO = 6;
    const LOG_DEBUG = 7;

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
     * Command handlers
     *
     * @var array
     */
    protected $commandHandlers = [];

    /**
     * Command handlers (instantiated for reutilization)
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
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Log level mapping to PSR-2
     *
     * @var array
     */
    private $logLevels;

    /**
     * Create a new Client
     *
     * @param array $servers Servers (`host`:`port`)
     */
    public function __construct(array $servers = ['127.0.0.1:7711'])
    {
        $this->setConnectionImplementation(Connection::class);
        foreach ($servers as $uri) {
            $port = 7711;
            if (strpos($uri, ':') !== false) {
                $server = parse_url($uri);
                if ($server === false || empty($server['host'])) {
                    continue;
                }
                $host = $server['host'];
                if (!empty($server['port'])) {
                    $port = $server['port'];
                }
            } else {
                $host = $uri;
            }
            $this->addServer($host, $port);
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
            $this->registerCommand($command, $handlerClass);
        }
    }

    /**
     * Set the connection implementation class
     *
     * @param string $class A fully classified class name that must implement ConnectionInterface
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
     * Sets the logger to use
     *
     * @param LoggerInterface $logger Logger
     * @throws InvalidArgumentException
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        if (!isset($this->logLevels)) {
            $this->logLevels = [
                self::LOG_EMERGENCY => LogLevel::EMERGENCY,
                self::LOG_ALERT => LogLevel::ALERT,
                self::LOG_CRITICAL => LogLevel::CRITICAL,
                self::LOG_ERROR => LogLevel::ERROR,
                self::LOG_WARNING => LogLevel::WARNING,
                self::LOG_NOTICE => LogLevel::NOTICE,
                self::LOG_INFO => LogLevel::INFO,
                self::LOG_DEBUG => LogLevel::DEBUG,
            ];
        }
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
     * @return ConnectionInterface
     * @throws ConnectionException
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
                $hello = $this->execute($connection, 'HELLO');
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
     * @param string $class Class that should implement Command\CommandInterface
     */
    public function registerCommand($command, $class)
    {
        if (!in_array(Command\CommandInterface::class, class_implements($class))) {
            throw new InvalidArgumentException("Class {$class} does not implement CommandInterface");
        }
        $this->commandHandlers[mb_strtoupper($command)] = $class;
    }

    /**
     * @throws Disque\Exception\InvalidCommandException
     */
    public function __call($command, array $arguments)
    {
        $command = mb_strtoupper($command);
        if (!isset($this->commandHandlers[$command])) {
            throw new InvalidCommandException($command);
        }
        return $this->execute($this->getConnection(), $command, $arguments);
    }

    /**
     * Execute the given command on the given connection
     *
     * @param ConnectionInterface $connection Connection
     * @param string $command Command
     * @param array $arguments Arguments for command
     * @return mixed Command response
     * @throws InvalidCommandException
     */
    protected function execute(ConnectionInterface $connection, $command, array $arguments = [])
    {
        if (!isset($this->commands[$command])) {
            $class = $this->commandHandlers[$command];
            $this->commands[$command] = new $class();
        }

        $command = $this->commands[$command];
        $command->setArguments($arguments);
        $response = $connection->execute($command);
        return $command->parse($response);
    }
}