<?php
namespace Disque\Exception;

use Disque\Command\CommandInterface;

class InvalidCommandArgumentException extends DisqueException
{
    public function __construct(CommandInterface $command, array $arguments)
    {
        parent::__construct(sprintf("Invalid command arguments. Arguments for command %1s: %2s",
            get_class($command),
            json_encode($arguments)
        ));
    }
}