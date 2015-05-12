<?php
namespace Disque\Queue\Marshal;

use Disque\Queue\JobInterface;

interface MarshalerInterface
{
    /**
     * Creates a JobInterface instance based on data obtained from queue
     *
     * @param string $source Source data
     * @return JobInterface
     * @throws MarshalException
     */
    public function unmarshal($source);

    /**
     * Marshals the body of the job ready to be put into the queue
     *
     * @param JobInterface $job Job to put in the queue
     * @return string Source data to be put in the queue
     */
    public function marshal(JobInterface $job);
}