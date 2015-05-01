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
    private $commands = [];

    public function __construct($host = '127.0.0.1', $port = 7711)
    {
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
        $response = $this->client->executeRaw($command->build($arguments));
        return $command->parse($response);
    }
}