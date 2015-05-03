<?php
namespace Disque\Command;

use Disque\Exception;

class QLen extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        return $this->buildStringArgument('QLEN', $arguments);
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