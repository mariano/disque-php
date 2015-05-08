<?php
namespace Disque\Command\Response;

use Disque\Command\CommandInterface;
use Disque\DisqueException;

class InvalidResponseException extends DisqueException
{
    public function __construct(CommandInterface $command, $response)
    {
        parent::__construct(sprintf("Invalid command response. Command %1s got: %2s",
            get_class($command),
            json_encode($response)
        ));
    }
}