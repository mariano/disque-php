<?php
namespace Disque\Command\Response;

use Disque\Command\CommandInterface;

interface ResponseInterface
{
    /**
     * Set command
     *
     * @param CommandInterface $command Command
     * @return void
     */
    public function setCommand(CommandInterface $command);

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function setBody($body);

    /**
     * Parse response
     *
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse();
}