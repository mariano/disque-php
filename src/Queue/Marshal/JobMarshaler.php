<?php
namespace Disque\Queue\Marshal;

use Disque\Queue\Job;
use Disque\Queue\JobInterface;

/**
 * Serialize and deserialize the job body
 *
 * Serialize the job body when adding the job to the queue,
 * deserialize it and instantiate a new Job object when reading the job
 * from the queue.
 *
 * This marshaler uses JSON serialization for the whole Job body.
 */
class JobMarshaler implements MarshalerInterface
{
    /**
     * @inheritdoc
     */
    public function unmarshal($source)
    {
        $body = json_decode($source, true);
        if (is_null($body)) {
            throw new MarshalException("Could not deserialize {$source}");
        }
        return new Job($body);
    }

    /**
     * @inheritdoc
     */
    public function marshal(JobInterface $job)
    {
        return json_encode($job->getBody());
    }
}
