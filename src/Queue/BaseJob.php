<?php
namespace Disque\Queue;

abstract class Job implements JobInterface
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
}