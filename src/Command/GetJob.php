<?php
namespace Disque\Command;

use Disque\Command\Argument\StringChecker;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\Response\JobsWithQueueResponse;

class GetJob extends BaseCommand implements CommandInterface
{
    use StringChecker;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = JobsWithQueueResponse::class;

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
     * Tells if this command blocks while waiting for a response, to avoid
     * being affected by connection timeouts.
     *
     * @return bool If true, this command blocks
     */
    public function isBlocking()
    {
        return true;
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @throws InvalidOptionException
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
                throw new InvalidOptionException($this, $last);
            }

            $options = $this->toArguments($options);
        }

        $this->checkStringArguments($arguments);
        $this->arguments = array_merge($options, ['FROM'], array_values($arguments));
    }
}