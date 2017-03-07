<?php

namespace Disque\Command;

use Disque\Command\Response\KeyValueResponse;
use Disque\Exception;

class QStat extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRING;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = KeyValueResponse::class;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'QSTAT';
    }
}
