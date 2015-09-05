<?php
namespace Disque\Queue;

abstract class BaseJob implements JobInterface
{
    /**
     * Job ID
     *
     * @var string
     */
    protected $id;

    /**
     * Job body
     *
     * This is the job data. Whether just an integer, an array, that depends
     * on the use case.
     *
     * @var mixed
     */
    protected $body;

    /**
     * The number of NACKs this job has received
     *
     * NACK is a command which tells Disque that the job wasn't processed
     * successfully and it should return to the queue immediately.
     *
     * @var int
     */
    protected $nacks = 0;

    /**
     * The number of times this job has been re-delivered for reasons other
     * than a NACK
     *
     * @var int
     */
    protected $additionalDeliveries = 0;

    /**
     * An optional shortcut for instantiating the job with one call
     *
     * To make it more flexible, all arguments in the constructor are optional
     * and the job can be populated by calling the setters.
     *
     * @param mixed  $body Body            The job body
     * @param string $id                   The job ID
     * @param int    $nacks                The number of NACKs
     * @param int    $additionalDeliveries The number of additional deliveries
     */
    public function __construct(
        $body = null,
        $id = null,
        $nacks = 0,
        $additionalDeliveries = 0
    ) {
        $this->body = $body;
        $this->id = $id;
        $this->nacks = $nacks;
        $this->additionalDeliveries = $additionalDeliveries;
    }

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getNacks()
    {
        return $this->nacks;
    }

    /**
     * @inheritdoc
     */
    public function setNacks($nacks)
    {
        $this->nacks = $nacks;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalDeliveries()
    {
        return $this->additionalDeliveries;
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalDeliveries($additionalDeliveries)
    {
        $this->additionalDeliveries = $additionalDeliveries;
    }
}
