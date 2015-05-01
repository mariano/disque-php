<?php
namespace Disque\Command;

class FastAck extends BaseJobModifierCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    protected function getCommand()
    {
        return 'FASTACK';
    }
}