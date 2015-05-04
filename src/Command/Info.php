<?php
namespace Disque\Command;

use Disque\Exception;

class Info extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_EMPTY;

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