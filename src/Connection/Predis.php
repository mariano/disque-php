<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
use Disque\Connection\Exception\ConnectionException;
use Disque\Connection\Exception\ResponseException;
use Predis\Client as PredisClient;

class Predis extends BaseConnection implements ConnectionInterface
{
    /**
     * Client
     *
     * @var Predis\Client
     */
    private $client;

    /**
     * Connect
     *
     * @param array $options Connection options
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function connect(array $options = [])
    {
        parent::connect($options);

        $this->client = new PredisClient([
            'scheme' => 'tcp',
            'host' => $this->host,
            'port' => $this->port
        ]);
        $this->client->connect();
    }

    /**
     * Disconnect
     */
    public function disconnect()
    {
        if (!$this->isConnected()) {
            return;
        }
        $this->client->disconnect();
        $this->client = null;
    }

    /**
     * Tells if connection is established
     *
     * @return bool Success
     */
    public function isConnected()
    {
        return $this->client->isConnected();
    }

    /**
     * Execute command, and get response
     *
     * @param Disque\Command\CommandInterface $command
     * @return mixed Response
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function execute(CommandInterface $command, array $arguments = [])
    {
        return $this->client->executeRaw($command->build($arguments));
    }
}