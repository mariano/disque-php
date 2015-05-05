<?php
namespace Disque\Command;

use Disque\Command\Argument\StringChecker;
use Disque\Exception\InvalidCommandOptionException;

class GetJob extends BaseCommand implements CommandInterface
{
    use StringChecker;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = Response\JobsWithQueueResponse::class;

    /**
     * Available command options
     *
     * @var array
     */
    protected $options = [
        'count' => null,
        'timeout' => null
    ];

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $availableArguments = [
        'timeout' => 'TIMEOUT',
        'count' => 'COUNT',
    ];

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'GETJOB';
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @throws InvalidCommandOptionException
     */
    public function setArguments(array $arguments)
    {
        $options = [];
        $last = end($arguments);
        if (is_array($last)) {
            $options = $last;
            $arguments = array_slice($arguments, 0, -1);

            if (
                (isset($options['count']) && !is_int($options['count'])) ||
                (isset($options['timeout']) && !is_int($options['timeout']))
            ) {
                throw new InvalidCommandOptionException($this, $last);
            }

            $options = $this->toArguments($options);
        }

        $this->checkStringArguments($arguments);
        $this->arguments = array_merge($options, ['FROM'], array_values($arguments));
    }
}