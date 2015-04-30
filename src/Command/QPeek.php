<?php
namespace Disque\Command;

use Disque\Exception;

class QPeek extends BaseCommand implements CommandInterface
{
    /**
     * Validate the given arguments
     *
     * @param array $arguments Arguments
     * @return array|null Modified arguments (null to leave as-is)
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function validate(array $arguments)
    {
        if (
            empty($arguments) ||
            count($arguments) !== 2 ||
            !isset($arguments[0]) ||
            !isset($arguments[1]) ||
            !is_numeric($arguments[1])
        ) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return array Command (separated in parts)
     */
    public function build()
    {
        return ['QPEEK', $this->arguments[0], (int) $this->arguments[1]];
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
            if (
                !is_array($job) ||
                count($job) !== 2 ||
                !isset($job[0]) ||
                empty($job[1])
            ) {
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