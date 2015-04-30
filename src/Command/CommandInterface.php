<?php
namespace Disque\Command;

interface CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return string Command for Disque
     */
    public function __toString();

    /**
     * Set arguments for command
     *
     * @param array $arguments Arguments
     */
    public function setArguments(array $arguments);

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response);
}