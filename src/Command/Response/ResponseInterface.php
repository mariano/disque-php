<?php
namespace Disque\Command\Response;

use Disque\Command\CommandInterface;

interface ResponseInterface
{
    /**
     * Set command
     *
     * @param CommandInterface $command Command
     */
    public function setCommand(CommandInterface $command);

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function setBody($body);

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse();
}