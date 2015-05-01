<?php
namespace Disque\Command;

use Disque\Exception;

class Dequeue extends BaseCommand implements CommandInterface
{
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
        return array_merge(['DEQUEUE'], $arguments);
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return array Jobs (each with 'id', 'body')
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