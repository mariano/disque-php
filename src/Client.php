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
     * Host to connect to
     *
     * @var string
     */
    private $host;

    /**
     * Port to connect to
     *
     * @var int
     */
    private $port;

    /**
     * Connection
     *
     * @var Disque\Connection\ConnectionInterface
     */
    private $connection;

    /**
     * Command handlers
     *
     * @var array
     */
    private $commands = [];

    /**
     * Connection implementation class
     *
     * @var string
     */
    private $connectionImplementation;

    public function __construct($host = '127.0.0.1', $port = 7711)
    {
        $this->setConnectionImplementation(Connection::class);
        $this->setHost($host);
        $this->setPort($port);

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

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        if (!is_int($port)) {
            throw new InvalidArgumentException("Invalid port: {$port}");
        }
        $this->port = $port;
    }

    /**
     * Connect to Disque
     *
     * @param array $options Connection options
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function connect(array $options = [])
    {
        $this->connection = $this->getConnection();
        $this->connection->connect($options);
        return $this->hello();
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

        $command = $this->commands[$command];
        $response = $this->connection->execute($command, $arguments);
        return $command->parse($response);
    }

    /**
     * Get connection
     *
     * @return Disque\Connection\ConnectionInterface
     */
    protected function getConnection()
    {
        if (!isset($this->connection)) {
            $class = $this->connectionImplementation;
            $this->connection = new $class();
            $this->connection->setHost($this->getHost());
            $this->connection->setPort($this->getPort());
        }
        return $this->connection;
    }
}