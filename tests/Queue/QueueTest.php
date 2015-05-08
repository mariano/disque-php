<?php
namespace Disque\Test\Queue;

use DateTime;
use Disque\Client;
use Disque\Queue\Job;
use Disque\Queue\JobInterface;
use Disque\Queue\JobNotAvailableException;
use Disque\Queue\Queue;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;

class MockJob extends Job
{
}

class QueueTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $q = new Queue(new Client(), 'queue');
        $this->assertInstanceOf(Queue::class, $q);
    }

    public function testSetJobClassInvalidClass()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Class DateTime does not implement JobInterface');
        $q = new Queue(new Client(), 'queue');
        $q->setJobClass(DateTime::class);
    }

    public function testSetJobClass()
    {
        $q = new Queue(new Client(), 'queue');
        $q->setJobClass(m::mock(JobInterface::class));
    }

    public function testPushConnected()
    {
        $payload = ['test' => 'stuff'];
        $job = m::mock(JobInterface::class)
            ->shouldReceive('dump')
            ->with()
            ->andReturn(json_encode($payload))
            ->once()
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('addJob')
            ->with('queue', json_encode($payload), [])
            ->andReturn('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $result = $q->push($job);
        $this->assertSame($job, $result);
    }

    public function testPushNotConnected()
    {
        $payload = ['test' => 'stuff'];
        $job = m::mock(JobInterface::class)
            ->shouldReceive('dump')
            ->with()
            ->andReturn(json_encode($payload))
            ->once()
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(false)
            ->once()
            ->shouldReceive('connect')
            ->with()
            ->once()
            ->shouldReceive('addJob')
            ->with('queue', json_encode($payload), [])
            ->andReturn('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $result = $q->push($job);
        $this->assertSame($job, $result);
    }

    public function testPushWithOptions()
    {
        $payload = ['test' => 'stuff'];
        $job = m::mock(JobInterface::class)
            ->shouldReceive('dump')
            ->with()
            ->andReturn(json_encode($payload))
            ->once()
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('addJob')
            ->with('queue', json_encode($payload), ['delay' => 3000])
            ->andReturn('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $result = $q->push($job, ['delay' => 3000]);
        $this->assertSame($job, $result);
    }

    public function testProcessedConnected()
    {
        $job = m::mock(JobInterface::class)
            ->shouldReceive('getId')
            ->with()
            ->andReturn('JOB_ID')
            ->once()
            ->mock();

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('ackJob')
            ->with('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $q->processed($job);
    }

    public function testProcessedNotConnected()
    {
        $job = m::mock(JobInterface::class)
            ->shouldReceive('getId')
            ->with()
            ->andReturn('JOB_ID')
            ->once()
            ->mock();

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(false)
            ->once()
            ->shouldReceive('connect')
            ->with()
            ->once()
            ->shouldReceive('ackJob')
            ->with('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $q->processed($job);
    }

    public function testPullConnected()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 0, 'count' => 1])
            ->andReturn([
                ['id' => 'JOB_ID', 'body' => json_encode($payload)]
            ])
            ->mock();

        $q = new Queue($client, 'queue');
        $job = $q->pull();
        $this->assertSame('JOB_ID', $job->getId());
        $this->assertSame($payload, $job->getBody());
    }

    public function testPullNotConnected()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(false)
            ->once()
            ->shouldReceive('connect')
            ->with()
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 0, 'count' => 1])
            ->andReturn([
                ['id' => 'JOB_ID', 'body' => json_encode($payload)]
            ])
            ->mock();

        $q = new Queue($client, 'queue');
        $job = $q->pull();
        $this->assertInstanceOf(Job::class, $job);
        $this->assertSame('JOB_ID', $job->getId());
        $this->assertSame($payload, $job->getBody());
    }

    public function testPullCustomClass()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 0, 'count' => 1])
            ->andReturn([
                ['id' => 'JOB_ID', 'body' => json_encode($payload)]
            ])
            ->mock();

        $q = new Queue($client, 'queue');
        $q->setJobClass(MockJob::class);
        $job = $q->pull();
        $this->assertInstanceOf(MockJob::class, $job);
        $this->assertSame('JOB_ID', $job->getId());
        $this->assertSame($payload, $job->getBody());
    }

    public function testPullSeveralJobs()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 0, 'count' => 1])
            ->andReturn([
                ['id' => 'JOB_ID', 'body' => json_encode($payload)],
                ['id' => 'JOB_ID_2', 'body' => json_encode([])],
            ])
            ->mock();

        $q = new Queue($client, 'queue');
        $job = $q->pull();
        $this->assertSame('JOB_ID', $job->getId());
        $this->assertSame($payload, $job->getBody());
    }

    public function testPullNoJobs()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 0, 'count' => 1])
            ->andReturn([])
            ->mock();

        $q = new Queue($client, 'queue');

        $this->setExpectedException(JobNotAvailableException::class);

        $q->pull();
    }

    public function testPullNoJobsWithTimeout()
    {
        $payload = ['test' => 'stuff'];

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('getJob')
            ->with('queue', ['timeout' => 3000, 'count' => 1])
            ->andReturn([])
            ->mock();

        $q = new Queue($client, 'queue');

        $this->setExpectedException(JobNotAvailableException::class);

        $q->pull(3000);
    }
}