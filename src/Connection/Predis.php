<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
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
     * @inheritdoc
     */
    public function connect($connectionTimeout = null, $responseTimeout = null)

    {
        parent::connect($connectionTimeout, $responseTimeout);

        $this->client = $this->buildClient($this->host, $this->port);
        $this->client->connect();
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function isConnected()
    {
        return (isset($this->client) && $this->client->isConnected());
    }

    /**
     * @inheritdoc
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
        return new PredisClient(['scheme' => 'tcp'] + compact('host', 'port'));
    }
}
