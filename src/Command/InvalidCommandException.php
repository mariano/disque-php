<?php
namespace Disque\Command;

use Disque\DisqueException;

class InvalidCommandException extends DisqueException
{
    public function __construct($command)
    {
        parent::__construct("Invalid command {$command}");
    }
}