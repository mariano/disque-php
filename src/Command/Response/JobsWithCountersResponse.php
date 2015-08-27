<?php
namespace Disque\Command\Response;

/**
 * Parse a Disque response of GETJOB with the argument WITHCOUNTERS
 */
class JobsWithCountersResponse extends JobsWithQueueResponse implements ResponseInterface
{
    const KEY_NACKS = 'nacks';
    const KEY_ADDITIONAL_DELIVERIES = 'additional-deliveries';

    /**
     * GETJOB called with WITHCOUNTERS returns a 7-member array for each job.
     * The value on (zero-based) position #3 is always the string "nacks", the
     * value on position #5 is always the string "additional-deliveries".
     *
     * We want to remove these values from the response.
     */
    const DISQUE_RESPONSE_KEY_NACKS = 3;
    const DISQUE_RESPONSE_KEY_DELIVERIES = 5;

    public function __construct()
    {
        // Note: The order of these calls is important as $jobDetails must
        // match the order of the Disque response rows. Nacks go at the end.
        parent::__construct();
        $this->jobDetails = array_merge(
            $this->jobDetails,
            [
                self::KEY_NACKS,
                self::KEY_ADDITIONAL_DELIVERIES
            ]
        );
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

        $jobDetailCount = count($this->jobDetails) + 2;

        /**
         * Remove superfluous strings from the response
         * See the comment for the constants defined above
         */
        $filteredBody = array_map(
            function(array $job) use ($jobDetailCount, $body) {
                if (!$this->checkFixedArray($job, $jobDetailCount)) {
                    throw new InvalidResponseException($this->command, $body);
                }

                unset($job[self::DISQUE_RESPONSE_KEY_NACKS]);
                unset($job[self::DISQUE_RESPONSE_KEY_DELIVERIES]);

                /**
                 * We must reindex the array so it's dense (without holes)
                 * @see Disque\Command\Argument\ArrayChecker::checkFixedArray()
                 */
                return array_values($job);
            }, $body);



        parent::setBody($filteredBody);
    }
}
