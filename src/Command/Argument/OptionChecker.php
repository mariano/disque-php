<?php
namespace Disque\Command\Argument;

use Disque\Command\Argument\InvalidOptionException;

trait OptionChecker
{
    /**
     * Checks an array so that their keys are ints
     *
     * @param array $options Options
     * @param array $keys Keys to check
     * @throw InvalidOptionException
     */
    protected function checkOptionsInt(array $options, array $keys)
    {
        foreach ($keys as $intOption) {
            if (isset($options[$intOption]) && !is_int($options[$intOption])) {
                throw new InvalidOptionException($this, $options);
            }
        }
    }

    /**
     * Checks an array so that their keys are strings
     *
     * @param array $options Options
     * @param array $keys Keys to check
     * @throw InvalidOptionException
     */
    protected function checkOptionsString(array $options, array $keys)
    {
        foreach ($keys as $intOption) {
            if (isset($options[$intOption]) && !is_string($options[$intOption])) {
                throw new InvalidOptionException($this, $options);
            }
        }
    }

    /**
     * Checks an array so that their keys are arrays
     *
     * @param array $options Options
     * @param array $keys Keys to check
     * @throw InvalidOptionException
     */
    protected function checkOptionsArray(array $options, array $keys)
    {
        foreach ($keys as $intOption) {
            if (isset($options[$intOption]) && !is_array($options[$intOption])) {
                throw new InvalidOptionException($this, $options);
            }
        }
    }
}