<?php
namespace Disque\Command;

use InvalidArgumentException;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\Argument\StringChecker;
use Disque\Exception;
use Disque\Command\Response\StringResponse;

abstract class BaseCommand implements CommandInterface
{
    use StringChecker;

    const ARGUMENTS_TYPE_EMPTY = 0;
    const ARGUMENTS_TYPE_STRING = 1;
    const ARGUMENTS_TYPE_STRING_INT = 2;
    const ARGUMENTS_TYPE_STRINGS = 3;

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
     * Tells which class handles the response
     *
     * @var string
     */
    protected $responseHandler = StringResponse::class;

    /**
     * Get command
     *
     * @return string Command
     */
    abstract public function getCommand();

    /**
     * Tells if this command blocks while waiting for a response, to avoid
     * being affected by connection timeouts.
     *
     * @return bool If true, this command blocks
     */
    public function isBlocking()
    {
        return false;
    }

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
     * @throws InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        switch ($this->argumentsType) {
            case self::ARGUMENTS_TYPE_EMPTY:
                if (!empty($arguments)) {
                    throw new InvalidCommandArgumentException($this, $arguments);
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
                    throw new InvalidCommandArgumentException($this, $arguments);
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
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws Disque\Command\Response\InvalidResponseException
     */
    public function parse($body)
    {
        $responseClass = $this->responseHandler;
        $response = new $responseClass();
        $response->setCommand($this);
        $response->setBody($body);
        return $response->parse();
    }

    /**
     * Build command arguments out of options
     *
     * @param array $options Command options
     * @return array Command arguments
     * @throws InvalidOptionException
     */
    protected function toArguments(array $options)
    {
        if (empty($options)) {
            return [];
        } elseif (!empty(array_diff_key($options, $this->availableArguments))) {
            throw new InvalidOptionException($this, $options);
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
}