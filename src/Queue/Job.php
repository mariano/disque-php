<?php
namespace Disque\Queue;

class Job extends BaseJob implements JobInterface
{
    /**
     * Marshals the body of the job ready to be put into the queue
     *
     * @return string Source data to be put in the queue
     */
    public function dump()
    {
        return json_encode($this->getBody());
    }

    /**
     * Set body of the job out of what was obtained from a queue
     *
     * @param string $source Source data
     * @return void
     * @throws MarshalException
     */
    public function load($source)
    {
        $body = @json_decode($source, true);
        if (is_null($body)) {
            throw new MarshalException("Could not deserialize {$source}");
        }
        $this->setBody($body);
    }
}