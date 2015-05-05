<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;
use Disque\Exception\InvalidCommandResponseException;

class JobsResponse extends BaseResponse implements ResponseInterface
{
    use ArrayChecker;

    /**
     * Job details for each job
     *
     * @var array
     */
    private $jobDetails = [];

    /**
     * Create
     *
     * @param bool $withQueue Tells wether response has queue specified for each job
     */
    public function __construct($withQueue = false)
    {
        $this->jobDetails = ($withQueue ? ['queue', 'id', 'body'] : ['id', 'body']);
    }

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidCommandResponseException
     */
    public function setBody($body)
    {
        if (empty($body) || !is_array($body)) {
            throw new InvalidCommandResponseException($this->command, $body);
        }
        $totalJobDetails = count($this->jobDetails);
        foreach ($body as $job) {
            if (!$this->checkFixedArray($job, $totalJobDetails)) {
                throw new InvalidCommandResponseException($this->command, $body);
            }
        }

        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @return array Parsed response
     * @throws InvalidCommandResponseException
     */
    public function parse()
    {
        $jobs = [];
        foreach ($this->body as $job) {
            $jobs[] = array_combine($this->jobDetails, $job);
        }
        return $jobs;
    }
}