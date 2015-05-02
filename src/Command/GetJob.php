<?php
namespace Disque\Command;

use Disque\Exception;

class GetJob extends BaseJobFetcherCommand implements CommandInterface
{
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
    protected $arguments = [
        'timeout' => 'TIMEOUT',
        'count' => 'COUNT',
    ];

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
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

        $queues = [];
        reset($arguments);
        foreach ($arguments as $argument) {
            if (!is_string($argument)) {
                throw new Exception\InvalidCommandArgumentException($this, $arguments);
            }
            $queues[] = $argument;
        }

        if (empty($queues)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        return array_merge(['GETJOB'], $options, ['FROM'], $queues);
    }

    /**
     * Get the job details provided in the response
     *
     * @return array Job detail fields
     */
    protected function getJobDetails()
    {
        return ['queue', 'id', 'body'];
    }
}