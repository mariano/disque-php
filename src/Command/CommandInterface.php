<?php
namespace Disque\Command;

interface CommandInterface
{
    /**
     * Get the command name
     *
     * The command name determines how the command will be called on Client.
     * If this method returns "foo", the command must be invoked by calling
     * the Client::foo() method (the method name is case insensitive)
     *
     * @return string Command
     */
    public function getCommand();

    /**
     * Tells if this command blocks while waiting for a response, to avoid
     * being affected by connection timeouts.
     *
     * @return bool If true, this command blocks
     */
    public function isBlocking();

    /**
     * Get processed arguments for command
     *
     * @return array Arguments
     */
    public function getArguments();

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @return void
     * @throws Disque\Command\Argument\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments);

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws Disque\Command\Response\InvalidResponseException
     */
    public function parse($body);
}
