<?php
namespace Disque\Command;

use Disque\Exception;

abstract class BaseJobFetcherCommand extends BaseCommand implements CommandInterface
{
    /**
     * Get the job details provided in the response
     *
     * @return array Job detail fields
     */
    abstract protected function getJobDetails();

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return array Jobs
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!is_array($response) || empty($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $jobDetails = $this->getJobDetails();
        $totalJobDetails = count($jobDetails);

        $jobs = [];
        foreach ($response as $job) {
            if (!$this->checkFixedArray($job, $totalJobDetails)) {
                throw new Exception\InvalidCommandResponseException($this, $response);
            }

            $jobs[] = array_combine($jobDetails, $job);
        }

        return $jobs;
    }
}