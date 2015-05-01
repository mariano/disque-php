<?php
namespace Disque\Command;

class Dequeue extends BaseJobModifierCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    protected function getCommand()
    {
        return 'DEQUEUE';
    }
}