<?php
namespace Disque\Command;

class AckJob extends BaseJobModifierCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    protected function getCommand()
    {
        return 'ACKJOB';
    }
}