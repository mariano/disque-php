<?php
namespace Disque;

use Disque\Command;
use Disque\Connection\Manager;
use Disque\Exception\InvalidCommandException;
use InvalidArgumentException;

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
     * Connection manager
     *
     * @var Manager
     */
    protected $connectionManager;

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
     * Create a new Client
     *
     * @param array $servers Servers (`host`:`port`)
     */
    public function __construct(array $servers = ['127.0.0.1:7711'])
    {
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

        $this->connectionManager = new Manager();
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
    }

    /**
     * Get connection manager
     *
     * @return Manager Connection manager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
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
        $this->connectionManager->addServer($host, $port);
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
        $this->connectionManager->setOptions($options);
        return $this->connectionManager->connect();
    }

    /**
     * @throws InvalidCommandException
     */
    public function __call($command, array $arguments)
    {
        $command = strtoupper($command);
        if (!isset($this->commandHandlers[$command])) {
            throw new InvalidCommandException($command);
        }

        if (!isset($this->commands[$command])) {
            $class = $this->commandHandlers[$command];
            $this->commands[$command] = new $class();
        }

        $command = $this->commands[$command];
        $command->setArguments($arguments);
        $result = $this->connectionManager->execute($command);
        return $command->parse($result);
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
        $this->commandHandlers[strtoupper($command)] = $class;
    }
}