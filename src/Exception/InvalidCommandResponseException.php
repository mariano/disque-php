<?php
namespace Disque\Exception;

use Disque\Command\CommandInterface;

class InvalidCommandResponseException extends DisqueException
{
    public function __construct(CommandInterface $command, array $response)
    {
        parent::__construct(sprintf("Invalid command response. Command %1s got: %2s",
            (string) $command,
            json_encode($response)
        ));
    }
}