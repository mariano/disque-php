<?php
namespace Disque\Exception;

class InvalidCommandException extends DisqueException
{
    public function __construct($command)
    {
        parent::__construct("Invalid command {$command}");
    }
}