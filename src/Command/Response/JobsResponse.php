<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

class JobsResponse extends BaseResponse implements ResponseInterface
{
    use ArrayChecker;

    const KEY_ID = 'id';
    const KEY_BODY = 'body';

    /**
     * The position where a node prefix starts in the job ID
     */
    const ID_NODE_PREFIX_START = 2;

    /**
     * Job details for each job
     *
     * The values in this array must follow these rules:
     * - The number of the values must be the same as the number of rows
     *   returned from the respective Disque command
     * - The order of the values must follow the rows returned by Disque
     *
     * The values in $jobDetails will be used as keys in the final response
     * the command returns.
     *
     * @see self::parse()
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
            // To describe this crucial moment in detail: $jobDetails as well as
            // the $job are numeric arrays with the same number of values.
            // array_combine() combines them in a new array so that values
            // from $jobDetails become the keys and values from $job the values.
            // It's very important that $jobDetails are present in the same
            // order as the response from Disque.
            $jobs[] = array_combine($this->jobDetails, $job);
        }
        return $jobs;
    }
}
