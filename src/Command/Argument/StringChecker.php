<?php
namespace Disque\Command\Argument;

trait StringChecker
{
    use ArrayChecker;

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @param int $numberOfElements Number of elements that must be present in $arguments
     * @throws InvalidCommandArgumentException
     */
    protected function checkStringArgument(array $arguments, $numberOfElements = 1)
    {
        if (!$this->checkFixedArray($arguments, $numberOfElements) || !is_string($arguments[0])) {
            throw new InvalidCommandArgumentException($this, $arguments);
        }
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @throws InvalidCommandArgumentException
     */
    protected function checkStringArguments(array $arguments)
    {
        if (empty($arguments)) {
            throw new InvalidCommandArgumentException($this, $arguments);
        }

        foreach ($arguments as $argument) {
            if (!is_string($argument) || $argument === '') {
                throw new InvalidCommandArgumentException($this, $arguments);
            }
        }
    }
}