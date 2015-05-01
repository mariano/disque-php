<?php
namespace Disque\Command;

use Disque\Exception;

class QPeek extends BaseJobFetcherCommand implements CommandInterface
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
     * Get the job details provided in the response
     *
     * @return array Job detail fields
     */
    protected function getJobDetails()
    {
        return ['id', 'body'];
    }
}