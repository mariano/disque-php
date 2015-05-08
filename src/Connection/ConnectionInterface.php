<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;

interface ConnectionInterface
{
    /**
     * Set host
     *
     * @param string $host Host
     * @return void
     */
    public function setHost($host);

    /**
     * Set port
     *
     * @param int $port Port
     * @return void
     */
    public function setPort($port);

    /**
     * Connect
     *
     * @param array $options Connection options
     * @return void
     * @throws ConnectionException
     */
    public function connect(array $options = []);

    /**
     * Disconnect
     *
     * @return void
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
     * @param CommandInterface $command
     * @return mixed Response
     * @throws ConnectionException
     */
    public function execute(CommandInterface $command);
}