<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

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
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        if (is_null($body)) {
            $body = [];
        }
        if (!is_array($body)) {
            throw new InvalidResponseException($this->command, $body);
        }
        $totalJobDetails = count($this->jobDetails);
        foreach ($body as $job) {
            if (!$this->checkFixedArray($job, $totalJobDetails)) {
                throw new InvalidResponseException($this->command, $body);
            }
            $id = ($totalJobDetails > 2 ? $job[1] : $job[0]);
            if (strpos($id, 'DI') !== 0 || strlen($id) < 10) {
                throw new InvalidResponseException($this->command, $body);
            }
        }

        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @return array Parsed response
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