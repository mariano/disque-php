<?php
namespace Disque\Command;

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
    protected $commandArguments = [];

    /**
     * Command arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Set arguments for command
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        $result = $this->validate($arguments);
        if (is_array($result)) {
            $arguments = $result;
        }
        $this->arguments = $arguments;
    }

    /**
     * Validate the given arguments
     *
     * @param array $arguments Arguments
     * @return array|null Modified arguments (null to leave as-is)
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function validate(array $arguments)
    {
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
     * Return command as string
     *
     * @return string Command
     */
    public function __toString()
    {
        return implode(' ', $this->build());
    }

    /**
     * Build command arguments out of options
     *
     * @param array $options Command options
     * @return array Command arguments
     */
    protected function optionsToArguments(array $options)
    {
        $arguments = [];

        foreach ($this->commandArguments as $argument => $option) {
            if (!isset($options[$option]) || $options[$option] === false) {
                continue;
            }

            $arguments[] = $argument;
            if (!is_bool($options[$option])) {
                $arguments[] = $options[$option];
            }
        }

        return $arguments;
    }
}