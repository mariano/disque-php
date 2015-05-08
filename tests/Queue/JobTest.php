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

    public function testSetBodyInvalidNotArray()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Job body should be an array');
        $j = new Job();
        $j->setBody('test');
    }

    public function testSetBodyEmpty()
    {
        $j = new Job();
        $this->assertSame([], $j->getBody());
    }

    public function testSetBody()
    {
        $j = new Job();
        $j->setBody(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
    }

    public function testSetBodyViaConstruct()
    {
        $j = new Job(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
    }

    public function testDumpEmpty()
    {
        $j = new Job();
        $result = $j->dump();
        $this->assertEquals('[]', $result);
    }

    public function testDump()
    {
        $payload = ['test' => 'stuff'];
        $j = new Job();
        $j->setBody($payload);
        $result = $j->dump();
        $this->assertEquals(json_encode($payload), $result);
    }

    public function testLoadInvalid()
    {
        $this->setExpectedException(MarshalException::class, 'Could not deserialize {"wrong"!');
        $j = new Job();
        $j->load('{"wrong"!');
    }

    public function testLoad()
    {
        $payload = ['test' => 'stuff'];
        $j = new Job();
        $this->assertSame([], $j->getBody());
        $j->load(json_encode($payload));
        $this->assertSame($payload, $j->getBody());
    }
}