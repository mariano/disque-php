<?php
namespace Disque\Command;

use Disque\Exception;

class Show extends BaseCommand implements CommandInterface
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
     * @return string Command for Disque
     */
    public function __toString()
    {
        return 'SHOW ' . $this->arguments[0];
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
        if ($response === false) {
            return null;
        } elseif (!is_array($response) || empty($response)) {
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