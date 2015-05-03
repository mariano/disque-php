<?php
namespace Disque;

use InvalidArgumentException;
use Disque\Command;
use Disque\Connection\Connection;
use Disque\Connection\ConnectionInterface;
use Disque\Exception;

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
    protected $servers;

    /**
     * Connection
     *
     * @var Disque\Connection\ConnectionInterface
     */
    protected $connection;

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
            $this->addServer($server);
        }

        foreach ([
            'ackjob' => Command\AckJob::class,
            'addjob' => Command\AddJob::class,
            'deljob' => Command\DelJob::class,
            'dequeue' => Command\Dequeue::class,
            'enqueue' => Command\Enqueue::class,
            'fastack' => Command\FastAck::class,
            'getjob' => Command\GetJob::class,
            'hello' => Command\Hello::class,
            'info' => Command\Info::class,
            'qlen' => Command\QLen::class,
            'qpeek' => Command\QPeek::class,
            'show' => Command\Show::class
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
     * @param array $server Server (should have 'host', and 'port')
     * @throws InvalidArgumentException
     */
    public function addServer(array $server)
    {
        $server += [
            'host' => '127.0.0.1',
            'port' => 7711
        ];
        if (!is_string($server['host']) || !is_int($server['port'])) {
            throw new InvalidArgumentException('Invalid server specified');
        }

        $this->servers[] = $server;
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
        $connectionClass = $this->connectionImplementation;
        $servers = $this->servers;
        $connection = null;
        $hello = [];
        while (!empty($servers)) {
            $key = array_rand($servers, 1);
            $server = $servers[$key];
            $connection = new $connectionClass();
            $connection->setHost($server['host']);
            $connection->setPort($server['port']);
            try {
                $connection->connect();
                $hello = $this->execute($connection, $this->commands['hello']);
                break;
            } catch (Exception\ConnectionException $e) {
                unset($servers[$key]);
                if (empty($servers))
                $connection = null;
                continue;
            }
        }

        if (!isset($connection)) {
            throw new Exception\ConnectionException('No servers available');
        }

        $this->connection = $connection;
        return $hello;
    }

    /**
     * Register a command handler
     *
     * @param string $command Command
     * @param Disque\Command\CommandInterface $handler Command handler
     */
    public function registerCommand($command, Command\CommandInterface $handler)
    {
        $this->commands[mb_strtolower($command)] = $handler;
    }

    /**
     * @throws Disque\Exception\InvalidCommandException
     */
    public function __call($command, array $arguments)
    {
        $command = mb_strtolower($command);
        if (!isset($this->commands[$command])) {
            throw new Exception\InvalidCommandException($command);
        }
        return $this->execute($this->connection, $this->commands[$command], $arguments);
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