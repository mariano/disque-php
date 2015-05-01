<?php
namespace Disque\Command;

use Disque\Exception;

class QPeek extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        if (!$this->checkFixedArray($arguments, 2) || !is_int($arguments[1])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
        return ['QPEEK', $arguments[0], (int) $arguments[1]];
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return array Jobs (each with 'id', 'body')
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!is_array($response) || empty($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $jobs = [];
        foreach ($response as $job) {
            if (!$this->checkFixedArray($job, 2)) {
                throw new Exception\InvalidCommandResponseException($this, $response);
            }

            $jobs[] = [
                'id' => $job[0],
                'body' => $job[1]
            ];
        }

        return $jobs;
    }
}