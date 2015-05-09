<?php
namespace Disque\Queue;

use InvalidArgumentException;

class Job implements JobInterface
{
    /**
     * Job ID
     *
     * @var string
     */
    private $id;

    /**
     * Job body
     *
     * @var array
     */
    private $body = [];

    /**
     * Build a job with the given body
     *
     * @param array $body Body
     */
    public function __construct(array $body = [])
    {
        $this->setBody($body);
    }

    /**
     * Creates a JobInterface instance based on data obtained from queue
     *
     * @param string $source Source data
     * @return JobInterface
     * @throws MarshalException
     */
    public static function load($source)
    {
        $body = @json_decode($source, true);
        if (is_null($body)) {
            throw new MarshalException("Could not deserialize {$source}");
        }
        return new static($body);
    }

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
     * Get job ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set job ID
     *
     * @param string $id Id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get job body
     *
     * @return array Job body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the job body
     *
     * @param array $body Body
     */
    public function setBody(array $body)
    {
        $this->body = $body;
    }
}