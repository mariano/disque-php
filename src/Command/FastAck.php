<?php
namespace Disque\Command;

class FastAck extends BaseCommand implements CommandInterface
{
    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_INT;

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        return $this->buildStringArguments('FASTACK', $arguments);
    }
}