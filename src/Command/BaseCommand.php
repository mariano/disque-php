<?php
namespace Disque\Command;

use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\Argument\StringChecker;
use Disque\Command\Response\StringResponse;

abstract class BaseCommand implements CommandInterface
{
    use StringChecker;

    /**
     * The following constants define the default behavior in case the command
     * uses the base method setArguments()
     *
     * If you override the method completely, then this has no effect.
     * @see GetJob
     */

     /**
     * The command doesn't accept any arguments
     * Eg. INFO
     */
    const ARGUMENTS_TYPE_EMPTY = 0;

    /**
     * The command accepts a single argument, a string
     * Eg. ACKJOB job_id
     */
    const ARGUMENTS_TYPE_STRING = 1;

    /**
     * The command accepts a single string argument followed by an integer
     * Eg. QPEEK queue_name 1
     */
    const ARGUMENTS_TYPE_STRING_INT = 2;

    /**
     * The command accepts only string arguments and there must be at least one
     * Eg. FASTACK job_id1 job_id2 ... job_idN
     */
    const ARGUMENTS_TYPE_STRINGS = 3;

    /**
     * Available command options
     *
     * Provide default argument values.
     * If the value for the argument is not provided, is null, or is false,
     * the option will not be used.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Available optional command arguments, and their mapping to options
     *
     * All available optional arguments must be defined here. If they are
     * processed by the method toArguments(), the $this->options variable
     * will automatically provide the default values.
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
            // A fallback in case a non-existing argument type is defined.
            // This could be prevented by using an Enum as the argument type
            default:
                throw new InvalidCommandArgumentException($this, $arguments);
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
     * Client-supplied options are amended with the default options defined
     * in $this->options. Options whose value is set to null or false are
     * ignored
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

        // Pad, don't overwrite, the client provided options with the default ones
        $options += $this->options;
        $arguments = [];
        foreach ($this->availableArguments as $option => $argument) {
            if (!isset($options[$option]) || $options[$option] === false) {
                continue;
            }

            $value = $options[$option];
            if (is_array($value)) {
                foreach ($value as $currentValue) {
                    $arguments[] = $argument;
                    $arguments[] = $currentValue;
                }
            } else {
                $arguments[] = $argument;
                if (!is_bool($value)) {
                    $arguments[] = $value;
                }
            }
        }

        return $arguments;
    }
}