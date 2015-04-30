<?php
namespace Disque;

use InvalidArgumentException;
use Disque\Command;
use Disque\Exception;
use Predis;

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
     * Client (phpredis)
     *
     * @var Redis
     */
    private $client;

    /**
     * Command handlers
     *
     * @var array
     */
    private $commandHandlers = [];

    public function __construct($host = '127.0.0.1', $port = 7711)
    {
        $this->setHost($host);
        $this->setPort($port);

        foreach ([
            'addjob' => Command\AddJob::class,
            'hello' => Command\Hello::class,
            'info' => Command\Info::class,
            'show' => Command\Show::class
        ] as $command => $handlerClass) {
            $this->registerCommand($command, $handlerClass);
        }
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
        if (!is_numeric($port)) {
            throw new InvalidArgumentException("Invalid port: {$port}");
        }
        $this->port = $port;
    }

    /**
     * Connect to Disque
     *
     * @throws Disque\Exception\ConnectionException
     */
    public function connect()
    {
        $host = $this->getHost();
        $port = $this->getPort();

        $this->client = new Predis\Client([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port
        ]);

        return $this->hello();
    }

    public function registerCommand($command, $handlerClass)
    {
        if (!class_exists($handlerClass) || !in_array(Command\CommandInterface::class, class_implements($handlerClass))) {
            throw new InvalidArgumentException("Command handler class {$handlerClass} does not exist, or does not implement CommandInterface");
        }

        $this->commandHandlers[mb_strtolower($command)] = $handlerClass;
    }

    /**
     * @throws Disque\Exception\InvalidCommandException
     */
    public function __call($command, array $arguments)
    {
        $command = mb_strtolower($command);
        if (!isset($this->commandHandlers[$command])) {
            throw new Exception\InvalidCommandException($command);
        }

        $class = $this->commandHandlers[$command];
        $command = new $class();
        $command->setArguments($arguments);
        $response = $this->client->executeRaw(explode(' ', (string) $command));
        return $command->parse($response);
    }
}