<?php
namespace Disque;

use Disque\Connection\Credentials;
use Disque\Command;
use Disque\Command\CommandInterface;
use Disque\Command\InvalidCommandException;
use Disque\Connection\Manager;
use Disque\Connection\ManagerInterface;
use Disque\Queue\Queue;
use InvalidArgumentException;

/**
 * @method int ackJob(string... $ids)
 * @method string addJob(string $queue, string $payload, array $options = [])
 * @method int delJob(string... $ids)
 * @method int dequeue(string... $ids)
 * @method int enqueue(string... $ids)
 * @method int fastAck(string... $ids)
 * @method array getJob(string... $queues, array $options = [])
 * @method array hello()
 * @method string info()
 * @method int nack(string... $ids)
 * @method int qlen(string $queue)
 * @method array qpeek(string $queue, int $count)
 * @method array qscan(array $options = [])
 * @method array show(string $id)
 * @method int working(string $id)
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
     * List of built queues
     *
     * @var array
     */
    private $queues;

    /**
     * A list of credentials to Disque servers
     *
     * @var Credentials[]
     */
    private $servers;

    /**
     * Create a new Client
     *
     * @param Credentials[] $servers
     */
    public function __construct(array $servers = [])
    {
        foreach ([
            new Command\AckJob(),
            new Command\AddJob(),
            new Command\DelJob(),
            new Command\Dequeue(),
            new Command\Enqueue(),
            new Command\FastAck(),
            new Command\GetJob(),
            new Command\Hello(),
            new Command\Info(),
            new Command\Nack(),
            new Command\QLen(),
            new Command\QPeek(),
            new Command\QScan(),
            new Command\Show(),
            new Command\Working()
        ] as $command) {
            $this->registerCommand($command);
        }

        $this->servers = $servers;

        $connectionManager = new Manager();
        $this->setConnectionManager($connectionManager);
    }

    /**
     * Set a connection manager
     *
     * @param ManagerInterface $manager
     */
    public function setConnectionManager(ManagerInterface $manager)
    {
        $this->connectionManager = $manager;
        foreach ($this->servers as $server) {
            $this->connectionManager->addServer($server);
        }
    }

    /**
     * Get the connection manager
     *
     * @return ManagerInterface Connection manager
     */
    public function getConnectionManager()
    {
        return $this->connectionManager;
    }

    /**
     * Tells if connection is established
     *
     * @return bool Success
     */
    public function isConnected()
    {
        return $this->connectionManager->isConnected();
    }

    /**
     * Connect to Disque
     *
     * @return array Connected node information
     * @throws Disque\Connection\ConnectionException
     */
    public function connect()
    {
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

        $command = $this->commandHandlers[$command];
        $command->setArguments($arguments);
        $result = $this->connectionManager->execute($command);
        return $command->parse($result);
    }

    /**
     * Register a command handler
     *
     * @param CommandInterface $commandHandler Command
     * @return void
     */
    public function registerCommand(CommandInterface $commandHandler)
    {
        $command = strtoupper($commandHandler->getCommand());
        $this->commandHandlers[$command] = $commandHandler;
    }

    /**
     * Get a queue
     *
     * @param string $name Queue name
     * @return Queue Queue
     */
    public function queue($name)
    {
        if (!isset($this->queues[$name])) {
            $this->queues[$name] = new Queue($this, $name);
        }
        return $this->queues[$name];
    }
}
