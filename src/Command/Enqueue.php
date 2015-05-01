<?php
namespace Disque\Command;

class Enqueue extends BaseJobModifierCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    protected function getCommand()
    {
        return 'ENQUEUE';
    }
}