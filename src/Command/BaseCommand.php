<?php
namespace Disque\Command;

use InvalidArgumentException;
use Disque\Exception;

abstract class BaseCommand implements CommandInterface
{
    /**
     * Available command options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param string $command Command
     * @param array $arguments Arguments
     * @param int $numberOfElements Number of elements that must be present in $arguments
     * @return array Command (separated in parts)
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function buildStringArgument($command, array $arguments, $numberOfElements = 1)
    {
        if (!$this->checkFixedArray($arguments, $numberOfElements) || !is_string($arguments[0])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
        return [$command, $arguments[0]];
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

    /**
     * Build command arguments out of options
     *
     * @param array $options Command options
     * @return array Command arguments
     * @throws Disque\Exception\InvalidCommandOptionException
     */
    protected function toArguments(array $options)
    {
        if (empty($options)) {
            return [];
        } elseif (!empty(array_diff_key($options, $this->arguments))) {
            throw new Exception\InvalidCommandOptionException($this, $options);
        }

        $options += $this->options;
        $arguments = [];
        foreach ($this->arguments as $option => $argument) {
            if (!isset($options[$option])) {
                continue;
            }

            $value = $options[$option];
            if (is_null($value) || $value === false) {
                continue;
            }

            $arguments[] = $argument;
            if (!is_bool($value)) {
                $arguments[] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Check that the exact specified $count arguments are defined,
     * in a numeric array
     *
     * @param mixed $elements Elements (should be an array)
     * @param int $count Number of elements expected
     * @param bool $atLeast Se to true to check array has at least $count elements
     * @return bool Success
     */
    protected function checkFixedArray($elements, $count, $atLeast = false)
    {
        if (
            empty($elements) ||
            !is_array($elements) ||
            (!$atLeast && count($elements) !== $count) ||
            ($atLeast && count($elements) < $count)
        ) {
            return false;
        }

        for ($i=0; $i < $count; $i++) {
            if (!isset($elements[$i])) {
                return false;
            }
        }

        return true;
    }
}