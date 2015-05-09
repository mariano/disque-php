<?php
namespace Disque\Queue;

interface JobInterface
{
    /**
     * Creates a JobInterface instance based on data obtained from queue
     *
     * @param string $source Source data coming from the queue
     * @return JobInterface
     * @throws MarshalException
     */
    public static function load($source);

    /**
     * Marshals the body of the job ready to be put into the queue
     *
     * @return string Source data to be put in the queue
     */
    public function dump();

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