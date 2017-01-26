<?php
namespace Disque\Command;

use Disque\Command\Argument\StringChecker;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\Response\JobsWithQueueResponse;
use Disque\Command\Response\JobsWithCountersResponse;

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
        'nohang' => false,
        'count' => null,
        'timeout' => null,
        'withcounters' => false
    ];

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $availableArguments = [
        'nohang' => 'NOHANG',
        'timeout' => 'TIMEOUT',
        'count' => 'COUNT',
        'withcounters' => 'WITHCOUNTERS'
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
        $arguments = $this->getArguments();
        $options = end($arguments);
        if (is_array($options) && isset($options['nohang']) && $options['nohang']) {
            return false;
        }
        return true;
    }

    /**
     * Set arguments for the command
     *
     * The $arguments must contain at least one, possibly more queue names
     * to read from. If the last value in the $arguments array is an array,
     * it can contain further optional arguments.
     * @see $availableArguments
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

            if (!empty($options['withcounters'])) {
                // The response will contain NACKs and additional-deliveries
                $this->responseHandler = JobsWithCountersResponse::class;
            }

            $options = $this->toArguments($options);
        }

        $this->checkStringArguments($arguments);
        $this->arguments = array_merge($options, ['FROM'], array_values($arguments));
    }
}
