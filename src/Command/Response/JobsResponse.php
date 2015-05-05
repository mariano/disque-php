<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;
use Disque\Exception\InvalidCommandResponseException;

class JobsResponse extends BaseResponse implements ResponseInterface
{
    use ArrayChecker;

    /**
     * Tells wether response has queue specified for each job
     *
     * @var bool
     */
    private $withQueue;

    /**
     * Create
     *
     * @param bool $withQueue Tells wether response has queue specified for each job
     */
    public function __construct($withQueue = false)
    {
        $this->withQueue = $withQueue;
    }

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @throws InvalidCommandResponseException
     */
    public function setBody($body)
    {
        if (empty($body) || !is_array($body)) {
            throw new InvalidCommandResponseException($this->command, $body);
        }
        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws InvalidCommandResponseException
     */
    public function parse()
    {
        $jobDetails = (
            $this->withQueue ?
            ['queue', 'id', 'body'] :
            ['id', 'body']
        );
        $totalJobDetails = count($jobDetails);

        $jobs = [];
        foreach ($this->body as $job) {
            if (!$this->checkFixedArray($job, $totalJobDetails)) {
                throw new InvalidCommandResponseException($this->command, $this->body);
            }

            $jobs[] = array_combine($jobDetails, $job);
        }

        return $jobs;
    }
}