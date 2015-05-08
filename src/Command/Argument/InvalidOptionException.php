<?php
namespace Disque\Command\Argument;

use Disque\Command\CommandInterface;

class InvalidOptionException extends InvalidCommandArgumentException
{
    public function __construct(CommandInterface $command, array $options)
    {
        parent::__construct($command, $options);
        $this->message = sprintf("Invalid command options. Options for command %1s: %2s",
            get_class($command),
            json_encode($options)
        );
    }
}