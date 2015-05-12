<?php
namespace Disque\Test\Queue\Marshaler;

use Disque\Queue\Job;
use Disque\Queue\JobInterface;
use Disque\Queue\Marshal\JobMarshaler;
use Disque\Queue\Marshal\MarshalerInterface;
use Disque\Queue\Marshal\MarshalException;
use Mockery as m;
use PHPUnit_Framework_TestCase;

class JobMarshalerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $m = new JobMarshaler();
        $this->assertInstanceOf(MarshalerInterface::class, $m);
    }

    public function testMarshalInvalid()
    {
        $job = m::mock(JobInterface::class);
        $this->setExpectedException(MarshalException::class, get_class($job) . ' is not a ' . Job::class);
        $m = new JobMarshaler();
        $m->marshal($job);
    }

    public function testMarshalEmpty()
    {
        $m = new JobMarshaler();
        $j = new Job();
        $result = $m->marshal($j);
        $this->assertSame('[]', $result);
    }

    public function testMarshalNotEmpty()
    {
        $m = new JobMarshaler();
        $j = new Job(['test' => 'stuff']);
        $result = $m->marshal($j);
        $this->assertSame('{"test":"stuff"}', $result);
    }

    public function testUnmarshalInvalid()
    {
        $this->setExpectedException(MarshalException::class, 'Could not deserialize {"wrong"!');
        $m = new JobMarshaler();
        $m->unmarshal('{"wrong"!');
    }

    public function testUnmarshalEmpty()
    {
        $m = new JobMarshaler();
        $j = $m->unmarshal('{"test":"stuff"}');
        $this->assertInstanceOf(Job::class, $j);
        $result = $j->getBody();
        $this->assertSame(['test' => 'stuff'], $result);
    }
}