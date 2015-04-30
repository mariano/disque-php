<?php
namespace Disque\Exception;

use Disque\Command\CommandInterface;

class InvalidCommandResponseException extends DisqueException
{
    public function __construct(CommandInterface $command, $response)
    {
        parent::__construct(sprintf("Invalid command response. Command %1s got: %2s",
            get_class($command),
            json_encode($response)
        ));
    }
}