<?php
namespace Disque\Command;

use Disque\Exception;

class QPeek extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRING_INT;

    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_JOBS;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'QPEEK';
    }
}