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
        $this->assertNull($j->getBody());
    }

    public function testBodyNotEmpty()
    {
        $body = ['test' => 'stuff'];
        $j = new Job($body);
        $this->assertSame($body, $j->getBody());
    }

    public function nullId()
    {
        $j = new Job();
        $this->assertNull($j->getId());
    }

    public function idNotNull()
    {
        $body = '';
        $id = 'id';
        $j = new Job($body, $id);
        $this->assertSame($id, $j->getId());
    }

    public function emptyQueue()
    {
        $j = new job();
        $this->assertEquals('', $j->getQueue());
    }

    public function queueSet()
    {
        $queue = 'queue';
        $j = new Job();
        $j->setQueue($queue);
        $this->assertEquals($queue, $j->getQueue());

    }

    public function zeroNacks()
    {
        $j = new Job();
        $this->assertSame(0, $j->getNacks());
    }

    public function nacksSet()
    {
        $j = new Job();
        $nacks = 10;
        $j->setNacks($nacks);
        $this->assertSame($nacks, $j->getNacks());
    }

    public function zeroAdditionalDeliveries()
    {
        $j = new Job();
        $this->assertSame(0, $j->getAdditionalDeliveries());
    }

    public function additionalDeliveriesSet()
    {
        $j = new Job();
        $deliveries = 10;
        $j->setAdditionalDeliveries($deliveries);
        $this->assertSame($deliveries, $j->getAdditionalDeliveries());
    }
}
