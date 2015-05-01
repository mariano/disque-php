<?php
namespace Disque\Command;

use Disque\Exception;

abstract class BaseJobModifierCommand extends BaseCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    abstract protected function getCommand();

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        if (empty($arguments)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        return array_merge([$this->getCommand()], $arguments);
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return int Number of jobs deleted
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!is_numeric($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }
        return (int) $response;
    }
}