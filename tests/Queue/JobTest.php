<?php
namespace Disque\Test\Queue;

use Disque\Queue\Job;
use Disque\Queue\JobInterface;
use PHPUnit_Framework_TestCase;

class JobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $j = new Job();
        $this->assertInstanceOf(JobInterface::class, $j);
    }

    public function testBodyEmpty()
    {
        $j = new Job();
        $this->assertSame([], $j->getBody());
    }

    public function testBodyNotEmpty()
    {
        $j = new Job(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
    }

    public function testSetBodyEmpty()
    {
        $j = new Job();
        $j->setBody([]);
        $this->assertSame([], $j->getBody());
    }

    public function testSetBodyNotEmpty()
    {
        $j = new Job();
        $j->setBody(['test' => 'stuff']);
        $this->assertSame(['test' => 'stuff'], $j->getBody());
    }
}