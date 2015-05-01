<?php
namespace Disque\Command;

use Disque\Exception;

class Info extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        if (!empty($arguments)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
        return ['INFO'];
    }
}