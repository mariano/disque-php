<?php
namespace Disque\Command;

use Disque\Exception;

abstract class BaseCommand implements CommandInterface
{
    /**
     * Command arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Set arguments for command
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        $this->validate($arguments);
        $this->arguments = $arguments;
    }

    /**
     * Validate the given arguments
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function validate(array $arguments)
    {
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