<?php
namespace Disque\Command;

use Disque\Exception;

class GetJob extends BaseCommand implements CommandInterface
{
    /**
     * Available command options
     *
     * @var array
     */
    protected $options = [
        'count' => null,
        'timeout' => 0
    ];

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $commandArguments = [
        'TIMEOUT' => 'timeout',
        'COUNT' => 'count',
    ];

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        $queues = [];
        $options = [];
        foreach ($arguments as $argument) {
            if (!is_string($argument) && !is_array($argument)) {
                throw new Exception\InvalidCommandArgumentException($this, $arguments);
            } elseif (is_array($argument) && !empty($options)) {
                throw new Exception\InvalidCommandArgumentException($this, $arguments);
            } elseif (is_array($argument)) {
                $options = $argument + $this->options;
                if (
                    (isset($options['count']) && !is_numeric($options['count'])) ||
                    (isset($options['timeout']) && !is_numeric($options['timeout']))
                ) {
                    throw new Exception\InvalidCommandArgumentException($this, $arguments);
                }
                continue;
            }
            $queues[] = $argument;
        }

        if (empty($queues)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        return array_merge(
            ['GETJOB'],
            $this->toArguments($options),
            ['FROM'],
            $queues
        );
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return array Jobs (each with 'queue', 'id', 'body')
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!is_array($response) || empty($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $jobs = [];
        foreach ($response as $job) {
            if (!$this->checkFixedArray($job, 3)) {
                throw new Exception\InvalidCommandResponseException($this, $response);
            }

            $jobs[] = [
                'queue' => $job[0],
                'id' => $job[1],
                'body' => $job[2]
            ];
        }

        return $jobs;
    }
}