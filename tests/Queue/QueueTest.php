<?php
namespace Disque\Test\Queue;

use DateTime;
use DateTimeZone;
use Disque\Client;
use Disque\Queue\Job;
use Disque\Queue\JobInterface;
use Disque\Queue\JobNotAvailableException;
use Disque\Queue\Queue;
use Disque\Queue\Marshal\MarshalerInterface;
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

    public function testMarshaler()
    {
        $q = new Queue(new Client(), 'queue');
        $q->setMarshaler(m::mock(MarshalerInterface::class));
    }

    public function testPushConnected()
    {
        $payload = ['test' => 'stuff'];
        $job = m::mock(Job::class.'[setId]')
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();
        $job->setBody($payload);

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
        $job = m::mock(Job::class.'[setId]')
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();
        $job->setBody($payload);

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
        $job = m::mock(Job::class.'[setId]')
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();
        $job->setBody($payload);

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

    public function testPushCustomMarshaler()
    {
        $payload = ['test' => 'stuff'];
        $job = m::mock(Job::class.'[setId]')
            ->shouldReceive('setId')
            ->with('JOB_ID')
            ->once()
            ->mock();
        $job->setBody($payload);

        $client = m::mock(Client::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->shouldReceive('addJob')
            ->with('queue', json_encode($payload), [])
            ->andReturn('JOB_ID')
            ->mock();

        $marshaler = m::mock(MarshalerInterface::class)
            ->shouldReceive('marshal')
            ->with($job)
            ->andReturn(json_encode($payload))
            ->once()
            ->mock();

        $q = new Queue($client, 'queue');
        $q->setMarshaler($marshaler);
        $result = $q->push($job);
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

    public function testFailedConnected()
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
            ->shouldReceive('nack')
            ->with('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $q->failed($job);
    }

    public function testFailedNotConnected()
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
            ->shouldReceive('nack')
            ->with('JOB_ID')
            ->mock();

        $q = new Queue($client, 'queue');
        $q->failed($job);
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

    public function testPullCustomMarshaler()
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

        $job = new Job();
        $marshaler = m::mock(MarshalerInterface::class)
            ->shouldReceive('unmarshal')
            ->with(json_encode($payload))
            ->andReturn($job)
            ->once()
            ->mock();

        $q = new Queue($client, 'queue');
        $q->setMarshaler($marshaler);
        $result = $q->pull();
        $this->assertSame($job, $result);
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

    public function testScheduleInvalidDateInPast()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Specified schedule time has passed');
        $date = new DateTime('-10 seconds');
        $q = new Queue(m::mock(Client::class), 'queue');
        $q->schedule(new Job(), $date);
    }

    public function testScheduleDefaultTimeZone()
    {
        $job = new Job();
        $queue = m::mock(Queue::class.'[push]', [m::mock(Client::class), 'queue'])
            ->shouldReceive('push')
            ->with($job, ['delay' => 10])
            ->andReturn($job)
            ->once()
            ->mock();

        $result = $queue->schedule($job, new DateTime('+10 seconds', new DateTimeZone(Queue::DEFAULT_JOB_TIMEZONE)));
        $this->assertSame($job, $result);
    }

    public function testScheduleDifferentTimeZone()
    {
        $job = new Job();
        $queue = m::mock(Queue::class.'[push]', [m::mock(Client::class), 'queue'])
            ->shouldReceive('push')
            ->with($job, ['delay' => 10])
            ->andReturn($job)
            ->once()
            ->mock();

        $timeZone = new DateTimeZone('America/Argentina/Buenos_Aires');
        $this->assertNotSame(Queue::DEFAULT_JOB_TIMEZONE, $timeZone->getName());
        $result = $queue->schedule($job, new DateTime('+10 seconds', $timeZone));
        $this->assertSame($job, $result);
    }

    public function testScheduleWayInTheFuture()
    {
        $job = new Job();
        $queue = m::mock(Queue::class.'[push]', [m::mock(Client::class), 'queue'])
            ->shouldReceive('push')
            ->with($job, ['delay' => (25 * 24 * 60 * 60)])
            ->andReturn($job)
            ->once()
            ->mock();

        $result = $queue->schedule($job, new DateTime('+25 days', new DateTimeZone(Queue::DEFAULT_JOB_TIMEZONE)));
        $this->assertSame($job, $result);
    }

    public function testProcessingConnected()
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
            ->shouldReceive('working')
            ->with('JOB_ID')
            ->andReturn(3)
            ->mock();

        $q = new Queue($client, 'queue');
        $result = $q->processing($job);
        $this->assertSame(3, $result);
    }

    public function testWorkingNotConnected()
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
            ->shouldReceive('working')
            ->with('JOB_ID')
            ->andReturn(3)
            ->mock();

        $q = new Queue($client, 'queue');
        $result = $q->processing($job);
        $this->assertSame(3, $result);
    }
}
