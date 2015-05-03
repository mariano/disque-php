<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
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
     * @param CommandInterface $command
     * @return mixed Response
     */
    public function execute(CommandInterface $command, array $arguments = [])
    {
        return $this->client->executeRaw($command->build($arguments));
    }
}