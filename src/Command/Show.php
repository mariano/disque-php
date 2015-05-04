<?php
namespace Disque\Command;

use Disque\Exception;

class Show extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRING;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'SHOW';
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