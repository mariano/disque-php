<?php
namespace Disque\Queue;

use InvalidArgumentException;

abstract class BaseJob implements JobInterface
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
     * Set body for job
     *
     * @param mixed $body Body
     * @return void
     * @throws InvalidArgumentException
     */
    public function setBody($body)
    {
        if (!is_array($body)) {
            throw new InvalidArgumentException('Job body should be an array');
        }
        $this->body = $body;
    }
}