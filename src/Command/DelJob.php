<?php
namespace Disque\Command;

class DelJob extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRINGS;

    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_INT;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'DELJOB';
    }
}