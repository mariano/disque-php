<?php
namespace Disque\Command;

use Disque\Command\Response\AuthResponse;

class Auth extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRING;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'AUTH';
    }
}