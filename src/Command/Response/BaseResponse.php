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
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Parse response
     *
     * @return mixed Parsed response
     * @throws InvalidResponseException
     */
    abstract public function parse();
}