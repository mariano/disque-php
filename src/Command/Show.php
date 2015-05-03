<?php
namespace Disque\Command;

use Disque\Exception;

class Show extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        return $this->buildStringArgument('SHOW', $arguments);
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return array Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if ($response === false) {
            return null;
        } elseif (!is_array($response) || empty($response) || (count($response) % 2) !== 0) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $result = [];
        $key = null;
        foreach ($response as $value) {
            if (!is_null($key)) {
                $result[$key] = $value;
                $key = null;
            } else {
                $key = $value;
            }
        }

        return $result;
    }
}