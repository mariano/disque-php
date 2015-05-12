<?php
namespace Disque\Queue;

interface JobInterface
{
    /**
     * Get job ID
     *
     * @return string
     */
    public function getId();

    /**
     * Set job ID
     *
     * @param string $id Id
     * @return void
     */
    public function setId($id);
}