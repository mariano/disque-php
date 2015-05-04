<?php
namespace Disque\Command;

use Disque\Exception;

class GetJob extends BaseCommand implements CommandInterface
{
    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_JOBS_WITH_QUEUE;

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
     * @throws Disque\Exception\InvalidCommandArgumentException
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
                throw new Exception\InvalidCommandOptionException($this, $last);
            }

            $options = $this->toArguments($options);
        }

        $this->checkStringArguments($arguments);
        $this->arguments = array_merge($options, ['FROM'], array_values($arguments));
    }
}