<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
use Disque\Connection\Exception\ConnectionException;

interface ConnectionInterface
{
    /**
     * Set host
     *
     * @param string $host Host
     */
    public function setHost($host);

    /**
     * Set port
     *
     * @param int $port Port
     */
    public function setPort($port);

    /**
     * Connect
     *
     * @param array $options Connection options
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function connect();

    /**
     * Disconnect
     */
    public function disconnect();

    /**
     * Tells if connection is established
     *
     * @return bool Success
     */
    public function isConnected();

    /**
     * Execute command, and get response
     *
     * @param Disque\Command\CommandInterface $command
     * @return mixed Response
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function execute(CommandInterface $command);
}