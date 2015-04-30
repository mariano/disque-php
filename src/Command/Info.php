<?php
namespace Disque\Command;

class Info extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return array Command (separated in parts)
     */
    public function build()
    {
        return ['INFO'];
    }
}