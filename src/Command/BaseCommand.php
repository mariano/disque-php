<?php
namespace Disque\Command;

abstract class BaseCommand implements CommandInterface
{
    /**
     * Command arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Create new command with given arguments
     *
     * @param array $arguments Arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->setArguments($arguments);
    }

    /**
     * Set arguments for command
     *
     * @param array $arguments Arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!is_string($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }
        return (string) $response;
    }
}