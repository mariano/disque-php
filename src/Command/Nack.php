<?php
namespace Disque\Command;

use Disque\Command\Response\IntResponse;

/**
 * Put the job(s) back to the queue immediately and increment the nack counter.
 *
 * The command should be used when the worker was not able to process a job and
 * wants the job to be put back into the queue in order to be processed again.
 *
 * It is very similar to ENQUEUE but it increments the job nacks counter
 * instead of the additional-deliveries counter.
 */
class Nack extends BaseCommand implements CommandInterface
{
    /**
     * @inheritdoc
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_STRINGS;
    
    /**
     * @inheritdoc
     */
    protected $responseHandler = IntResponse::class;
    
    /**
     * @inheritdoc
     */
    public function getCommand()
    {
        return 'NACK';
    }
}
