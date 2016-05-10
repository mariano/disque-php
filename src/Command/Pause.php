<?php
namespace Disque\Command;

use Disque\Command\Response\StringResponse;

/**
 * Pause a queue.
 */
class Pause extends BaseCommand implements CommandInterface
{
    /**
     * @inheritdoc
     */
    protected $responseHandler = StringResponse::class;

    /**
     * @inheritdoc
     */
    public function getCommand()
    {
        return 'PAUSE';
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @return void
     * @throws InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        $this->checkStringArgument($arguments, 2);
        $this->checkStringArguments($arguments);
        $this->arguments = $arguments;
    }
}