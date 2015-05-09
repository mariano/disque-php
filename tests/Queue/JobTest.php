<?php
namespace Disque\Test\Queue;

use InvalidArgumentException;
use Disque\Queue\Job;
use Disque\Queue\JobInterface;
use Disque\Queue\MarshalException;
use PHPUnit_Framework_TestCase;

class JobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $j = new Job();
        $this->assertInstanceOf(JobInterface::class, $j);
    }

    public function testSetId()
    {
        $j = new Job();
        $j->setId('MY_ID');
        $this->assertSame('MY_ID', $j->getId());
    }

    public function testBodyEmpty()
    {
        $j = new Job();
        $this->assertSame([], $j->getBody());
        $this->assertSame('[]', $j->dump());
    }

    public function testBodyNotEmpty()
    {
        $j = new Job(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
        $this->assertSame('{"test":"stuff"}', $j->dump());
    }

    public function testSetBodyEmpty()
    {
        $j = new Job();
        $j->setBody([]);
        $this->assertSame([], $j->getBody());
        $this->assertSame('[]', $j->dump());
    }

    public function testSetBodyNotEmpty()
    {
        $j = new Job();
        $j->setBody(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
        $this->assertSame('{"test":"stuff"}', $j->dump());
    }

    public function testLoadInvalid()
    {
        $this->setExpectedException(MarshalException::class, 'Could not deserialize {"wrong"!');
        Job::load('{"wrong"!');
    }

    public function testLoadEmpty()
    {
        $j = Job::load('{"test":"stuff"}');
        $this->assertInstanceOf(Job::class, $j);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
    }
}