<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
use Disque\Connection\Factory\ConnectionFactoryInterface;
use Disque\Connection\Node\NodePrioritizerInterface;

interface ManagerInterface
{
    /**
     * Get the connection factory
     *
     * @return ConnectionFactoryInterface
     */
    public function getConnectionFactory();

    /**
     * Set the connection factory
     *
     * @param ConnectionFactoryInterface $connectionFactory
     */
    public function setConnectionFactory(ConnectionFactoryInterface $connectionFactory);

    /**
     * Get credentials to all initially available nodes
     *
     * @return Credentials[]
     */
    public function getCredentials();

    /**
     * Add new server credentials
     *
     * @param Credentials $credentials
     *
     * @return void
     */
    public function addServer(Credentials $credentials);

    /**
     * Get the current node prioritizer
     *
     * @return NodePrioritizerInterface
     */
    public function getPriorityStrategy();

    /**
     * Set the node priority strategy
     *
     * @param NodePrioritizerInterface $priorityStrategy
     */
    public function setPriorityStrategy($priorityStrategy);

    /**
     * Tells if connection is established
     *
     * @return bool Success
     */
    public function isConnected();

    /**
     * Connect to Disque
     *
     * @return Node The current node
     *
     * @throws AuthenticationException
     * @throws ConnectionException
     */
    public function connect();

    /**
     * Execute the given command on the given connection
     *
     * @param CommandInterface $command Command
     * @return mixed Command response
     */
    public function execute(CommandInterface $command);
}
