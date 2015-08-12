<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

class JobsResponse extends BaseResponse implements ResponseInterface
{
    use ArrayChecker;

    const KEY_ID = 'id';
    const KEY_BODY = 'body';

    /**
     * Job details for each job
     *
     * @var array
     */
    protected $jobDetails = [];

    public function __construct()
    {
        $this->jobDetails = [self::KEY_ID, self::KEY_BODY];
    }

    /**
     * @inheritdoc
     */
    public function setBody($body)
    {
        if (is_null($body)) {
            $body = [];
        }
        if (!is_array($body)) {
            throw new InvalidResponseException($this->command, $body);
        }

        $jobDetailCount = count($this->jobDetails);
        foreach ($body as $job) {
            if (!$this->checkFixedArray($job, $jobDetailCount)) {
                throw new InvalidResponseException($this->command, $body);
            }

            $idPosition = array_search(self::KEY_ID, $this->jobDetails);
            $id = $job[$idPosition];
            if (strpos($id, 'DI') !== 0 || strlen($id) < 10) {
                throw new InvalidResponseException($this->command, $body);
            }
        }

        parent::setBody($body);
    }

    /**
     * @inheritdoc
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
