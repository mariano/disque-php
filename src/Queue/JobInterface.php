<?php
namespace Disque\Queue;

interface JobInterface
{
    /**
     * Get the job ID
     *
     * @return string
     */
    public function getId();

    /**
     * Set the job ID
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Get the job body
     *
     * @return mixed Job body
     */
    public function getBody();

    /**
     * Set the job body
     *
     * @return mixed $body
     */
    public function setBody($body);

    /**
     * Get the name of the queue the job belongs to
     *
     * @return string
     */
    public function getQueue();

    /**
     * Set the name of the queue the job belongs to
     *
     * @param string $queue
     */
    public function setQueue($queue);

    /**
     * Get the number of NACKs
     *
     * The `nacks` counter is incremented every time a worker uses the `NACK`
     * command to tell the queue the job was not processed correctly and should
     * be put back on the queue.
     *
     * @return int
     */
    public function getNacks();

    /**
     * Set the number of NACKs
     *
     * @param int $nacks
     */
    public function setNacks($nacks);

    /**
     * Get the number of additional deliveries
     *
     * The `additional-deliveries` counter is incremented for every other
     * condition (different than `NACK` call) that requires a job to be put
     * back on the queue again. This includes jobs that get lost and are
     * enqueued again or jobs that are delivered multiple times because they
     * time out.
     *
     * @return int
     */
    public function getAdditionalDeliveries();

    /**
     * Set the number of additional deliveries
     *
     * @param int $additionalDeliveries
     */
    public function setAdditionalDeliveries($additionalDeliveries);
}
