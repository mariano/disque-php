<?php
namespace Disque\Command\Response;

use Disque\Command\CommandInterface;

abstract class BaseResponse implements ResponseInterface
{
    /**
     * Command
     *
     * @var CommandInterface
     */
    protected $command;

    /**
     * Response body
     *
     * @var mixed
     */
    protected $body;

    /**
     * Set command
     *
     * @param CommandInterface $command Command
     */
    public function setCommand(CommandInterface $command)
    {
        $this->command = $command;
    }

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    abstract public function parse();
}