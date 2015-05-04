<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
use Disque\Connection\Exception\ConnectionException;
use Predis\Client as PredisClient;

class Predis extends BaseConnection implements ConnectionInterface
{
    /**
     * Client
     *
     * @var \Predis\Client
     */
    protected $client;

    /**
     * Connect
     *
     * @param array $options Connection options
     */
    public function connect(array $options = [])
    {
        parent::connect($options);

        $this->client = $this->buildClient($this->host, $this->port);
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
        return (isset($this->client) && $this->client->isConnected());
    }

    /**
     * Execute command, and get response
     *
     * @param CommandInterface $command
     * @return mixed Response
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function execute(CommandInterface $command)
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('No connection established');
        }
        return $this->client->executeRaw(array_merge(
            [$command->getCommand()],
            $command->getArguments()
        ));
    }

    /**
     * Build Predis client
     *
     * @param string $host Host
     * @param int $port Port
     * @return Predis\Client Client
     */
    protected function buildClient($host, $port)
    {
        return new PredisClient([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port
        ]);
    }
}