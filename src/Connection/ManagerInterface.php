<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;

interface ManagerInterface
{
    /**
     * Get the connection implementation class
     *
     * @return string A fully classified class name that implements `Disque\Connection\ConnectionInterface`
     */
    public function getConnectionClass();

    /**
     * Set the connection implementation class
     *
     * @param string $class A fully classified class name that must implement `Disque\Connection\ConnectionInterface`
     * @throws InvalidArgumentException
     */
    public function setConnectionClass($class);

    /**
     * Get available servers
     *
     * @return array Each server is an indexed array with `host` and `port`
     */
    public function getServers();

    /**
     * Add a new server
     *
     * @param string $host Host
     * @param int $port Port
     * @throws InvalidArgumentException
     */
    public function addServer($host, $port = 7711);

    /**
     * Connect to Disque
     *
     * @param array $options Connection options
     * @return array Connected node information
     * @throws ConnectionException
     */
    public function connect(array $options);

    /**
     * Execute the given command on the given connection
     *
     * @param CommandInterface $command Command
     * @return mixed Command response
     */
    public function execute(CommandInterface $command);
}