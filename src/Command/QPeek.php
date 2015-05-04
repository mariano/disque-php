<?php
namespace Disque\Command;

use Disque\Exception;

class QPeek extends BaseCommand implements CommandInterface
{
    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_JOBS;

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function build(array $arguments)
    {
        $command = $this->buildStringArgument('QPEEK', $arguments, 2);
        if (!is_int($arguments[1])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
        $command[] = (int) $arguments[1];
        return $command;
    }
}