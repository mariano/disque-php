<?php
namespace Disque\Queue\Marshal;

use Disque\Queue\Job;
use Disque\Queue\JobInterface;

class JobMarshaler implements MarshalerInterface
{
    /**
     * Creates a JobInterface instance based on data obtained from queue
     *
     * @param string $source Source data
     * @return JobInterface
     * @throws MarshalException
     */
    public function unmarshal($source)
    {
        $body = @json_decode($source, true);
        if (is_null($body)) {
            throw new MarshalException("Could not deserialize {$source}");
        }
        return new Job($body);
    }

    /**
     * Marshals the body of the job ready to be put into the queue
     *
     * @param JobInterface $job Job to put in the queue
     * @return string Source data to be put in the queue
     */
    public function marshal(JobInterface $job)
    {
        if (!($job instanceof Job)) {
            throw new MarshalException(get_class($job) . ' is not a ' . Job::class);
        }
        return json_encode($job->getBody());
    }
}