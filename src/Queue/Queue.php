<?php
namespace Disque\Queue;

use Disque\Client;
use InvalidArgumentException;

class Queue
{
    /**
     * Client
     *
     * @var Client
     */
    protected $client;

    /**
     * Name
     *
     * @var string
     */
    protected $name;

    /**
     * Job class
     *
     * @var string
     */
    private $jobClass;

    /**
     * Create a queue
     *
     * @param Client $client Client
     * @param string $name Queue name
     */
    public function __construct(Client $client, $name)
    {
        $this->client = $client;
        $this->name = $name;
        $this->jobClass = Job::class;
    }

    /**
     * Set Job implementation class
     *
     * @param string $class Job class which must implement `JobInterface`
     * @return void
     */
    public function setJobClass($class)
    {
        if (!in_array(JobInterface::class, class_implements($class))) {
            throw new InvalidArgumentException("Class {$class} does not implement JobInterface");
        }
        $this->jobClass = $class;
    }

    /**
     * Pushes a job into the queue
     *
     * @param JobInterface $job Job
     * @param array $options ADDJOB options sent to the client
     * @return JobInterface Job pushed (has ID set)
     */
    public function push(JobInterface $job, array $options = [])
    {
        $this->checkConnected();
        $id = $this->client->addJob($this->name, $job->dump(), $options);
        $job->setId($id);
        return $job;
    }

    /**
     * Pulls a single job from the queue (if none available, and if $timeout
     * specified, then wait only this much time for a job, otherwise throw a
     * `JobNotAvailableException`)
     *
     * @param int $timeout If specified, wait these many seconds
     * @return Job
     * @throws JobNotAvailableException
     */
    public function pull($timeout = 0)
    {
        $this->checkConnected();
        $jobs = $this->client->getJob($this->name, [
            'timeout' => $timeout,
            'count' => 1
        ]);
        if (empty($jobs)) {
            throw new JobNotAvailableException();
        }
        $jobData = $jobs[0];
        $class = $this->jobClass;
        $job = new $class();
        $job->setId($jobData['id']);
        $job->load($jobData['body']);
        return $job;
    }

    /**
     * Acknowledges a Job as properly handled
     *
     * @param JobInterface $job Job
     * @return void
     */
    public function processed(JobInterface $job)
    {
        $this->checkConnected();
        $this->client->ackJob($job->getId());
    }

    /**
     * Check that we are connected to a node, and if not connect
     *
     * @throws Disque\Connection\ConnectionException
     */
    private function checkConnected()
    {
        if (!$this->client->isConnected()) {
            $this->client->connect();
        }
    }
}