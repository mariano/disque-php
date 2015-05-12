<?php
namespace Disque\Queue;

abstract class BaseJob implements JobInterface
{
    /**
     * Job ID
     *
     * @var string
     */
    private $id;

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