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
}