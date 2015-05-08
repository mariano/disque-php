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

    /**
     * Get job body
     *
     * @return array Job body
     */
    public function getBody();

    /**
     * Set body for job
     *
     * @param mixed $body Body
     * @return void
     */
    public function setBody($body);

    /**
     * Marshals the body of the job ready to be put into the queue
     *
     * @return string Source data to be put in the queue
     */
    public function dump();

    /**
     * Set body of the job out of what was obtained from a queue
     *
     * @param string $source Source data
     * @return void
     * @throws MarshalException
     */
    public function load($source);
}