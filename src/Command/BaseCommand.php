<?php
namespace Disque\Command;

use InvalidArgumentException;
use Disque\Exception;

abstract class BaseCommand implements CommandInterface
{
    const ARGUMENTS_TYPE_EMPTY = 0;
    const ARGUMENTS_TYPE_STRING = 1;
    const ARGUMENTS_TYPE_STRING_INT = 2;
    const ARGUMENTS_TYPE_STRINGS = 3;
    const RESPONSE_TYPE_STRING = 0;
    const RESPONSE_TYPE_INT = 1;
    const RESPONSE_TYPE_JOBS = 2;
    const RESPONSE_TYPE_JOBS_WITH_QUEUE = 3;

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
    protected $availableArguments = [];

    /**
     * Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRING;

    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_STRING;

    /**
     * Get command
     *
     * @return string Command
     */
    abstract public function getCommand();

    /**
     * Get processed arguments for command
     *
     * @return array Arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @return void
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        switch ($this->argumentsType) {
            case self::ARGUMENTS_TYPE_EMPTY:
                if (!empty($arguments)) {
                    throw new Exception\InvalidCommandArgumentException($this, $arguments);
                }
                $arguments = [];
                break;
            case self::ARGUMENTS_TYPE_STRING:
                $this->checkStringArgument($arguments);
                $arguments = [$arguments[0]];
                break;
            case self::ARGUMENTS_TYPE_STRING_INT:
                $this->checkStringArgument($arguments, 2);
                if (!is_int($arguments[1])) {
                    throw new Exception\InvalidCommandArgumentException($this, $arguments);
                }
                $arguments = [$arguments[0], (int) $arguments[1]];
                break;
            case self::ARGUMENTS_TYPE_STRINGS:
                $this->checkStringArguments($arguments);
                break;
        }
        $this->arguments = $arguments;
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @param int $numberOfElements Number of elements that must be present in $arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function checkStringArgument(array $arguments, $numberOfElements = 1)
    {
        if (!$this->checkFixedArray($arguments, $numberOfElements) || !is_string($arguments[0])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function checkStringArguments(array $arguments)
    {
        if (empty($arguments)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        foreach ($arguments as $argument) {
            if (!is_string($argument) || $argument === '') {
                throw new Exception\InvalidCommandArgumentException($this, $arguments);
            }
        }
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
        switch ($this->responseType) {
            case self::RESPONSE_TYPE_INT:
                if (!is_numeric($response)) {
                    throw new Exception\InvalidCommandResponseException($this, $response);
                }
                return (int) $response;
            case self::RESPONSE_TYPE_JOBS:
            case self::RESPONSE_TYPE_JOBS_WITH_QUEUE:
                if (!is_array($response) || empty($response)) {
                    throw new Exception\InvalidCommandResponseException($this, $response);
                }
                return $this->parseJobs($this->responseType, (array) $response);
            case self::RESPONSE_TYPE_STRING:
            default:
                if (!is_string($response)) {
                    throw new Exception\InvalidCommandResponseException($this, $response);
                }
                return (string) $response;
        }
    }

    /**
     * Parse response
     *
     * @param int $responseType Response type
     * @param array $response Response
     * @return array Jobs
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    private function parseJobs($responseType, array $response)
    {
        $jobDetails = (
            $responseType === self::RESPONSE_TYPE_JOBS_WITH_QUEUE ?
            ['queue', 'id', 'body'] :
            ['id', 'body']
        );
        $totalJobDetails = count($jobDetails);

        $jobs = [];
        foreach ($response as $job) {
            if (!$this->checkFixedArray($job, $totalJobDetails)) {
                throw new Exception\InvalidCommandResponseException($this, $response);
            }

            $jobs[] = array_combine($jobDetails, $job);
        }

        return $jobs;
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
        } elseif (!empty(array_diff_key($options, $this->availableArguments))) {
            throw new Exception\InvalidCommandOptionException($this, $options);
        }

        $options += $this->options;
        $arguments = [];
        foreach ($this->availableArguments as $option => $argument) {
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