<?php
namespace Disque\Command;

class DelJob extends BaseJobModifierCommand implements CommandInterface
{
    /**
     * Get the command name
     *
     * @return string
     */
    protected function getCommand()
    {
        return 'DELJOB';
    }
}