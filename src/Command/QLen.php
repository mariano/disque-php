<?php
namespace Disque\Command;

use Disque\Exception;

class QLen extends BaseCommand implements CommandInterface
{
    /**
     * Validate the given arguments
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function validate(array $arguments)
    {
        if (count($arguments) !== 1 || !isset($arguments[0])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return array Command (separated in parts)
     */
    public function build()
    {
        return ['QLEN', $this->arguments[0]];
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