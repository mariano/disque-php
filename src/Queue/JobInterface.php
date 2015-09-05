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
     * Get the number of NACKs
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
