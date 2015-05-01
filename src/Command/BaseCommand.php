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
    protected $arguments = [];

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
     */
    protected function toArguments(array $options)
    {
        $arguments = [];

        foreach ($this->arguments as $argument => $option) {
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