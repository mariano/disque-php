<?php
namespace Disque\Command;

class FastAck extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRINGS;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = Response\IntResponse::class;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'FASTACK';
    }
}