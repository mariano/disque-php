<?php
namespace Disque\Command;

use Disque\Command\Response\InfoResponse;

class Info extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_EMPTY;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = InfoResponse::class;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'INFO';
    }
}
