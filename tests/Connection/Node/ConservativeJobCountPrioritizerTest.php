<?php
namespace Disque\Test\Connection\Node;

use Disque\Connection\Node\ConservativeJobCountPrioritizer;
use Disque\Connection\Node\Node;
use InvalidArgumentException;
use Mockery as m;

class ConservativeJobCountPrioritizerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $p = new ConservativeJobCountPrioritizer();
        $this->assertInstanceOf(ConservativeJobCountPrioritizer::class, $p);
    }

    public function testDefaultMarginToSwitch()
    {
        $p = new ConservativeJobCountPrioritizer();
        $this->assertSame(0.05, $p->getMarginToSwitch());
    }

    public function testSetMarginToSwitch()
    {
        $marginToSwitch = 0.5;
        $p = new ConservativeJobCountPrioritizer();
        $p->setMarginToSwitch($marginToSwitch);
        $this->assertSame($marginToSwitch, $p->getMarginToSwitch());
    }

    public function testSetInvalidMarginToSwitch()
    {
        $marginToSwitch = -0.5;
        $p = new ConservativeJobCountPrioritizer();
        $this->setExpectedException(InvalidArgumentException::class, 'Margin to switch must not be negative');
        $p->setMarginToSwitch($marginToSwitch);
    }

    public function testSortWithDefaultMarginToSwitch()
    {
        $nodeId1 = 'id1';
        $nodeId2 = 'id2';
        $nodeId3 = 'id3';
        $node1 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(100)
            ->shouldReceive('getId')
            ->andReturn($nodeId1)
            ->getMock();
        $node2 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(104)
            ->shouldReceive('getId')
            ->andReturn($nodeId2)
            ->getMock();
        $node3 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(106)
            ->shouldReceive('getId')
            ->andReturn($nodeId3)
            ->getMock();

        $nodes = [
            $nodeId1 => $node1,
            $nodeId2 => $node2
        ];
        $expectedNodes = $nodes;
        $p = new ConservativeJobCountPrioritizer();
        $resultNodes = $p->sort($nodes, $nodeId1);
        $this->assertSame($expectedNodes, $resultNodes);

        $nodes2 = [
            $nodeId1 => $node1,
            $nodeId3 => $node3
        ];
        $expectedNodes2 = [
            $nodeId3 => $node3,
            $nodeId1 => $node1
        ];
        $resultNodes2 = $p->sort($nodes2, $node1);
        $this->assertSame($expectedNodes2, $resultNodes2);
    }

    public function testSingleNodeIsNotSorted()
    {
        $nodeId1 = 'id1';
        $node1 = m::mock(Node::class);
        $nodes = [$nodeId1 => $node1];

        $p = new ConservativeJobCountPrioritizer();
        $resultNodes = $p->sort($nodes, $nodeId1);
        $this->assertSame($nodes, $resultNodes);
    }

    public function testSortWithCustomMargin()
    {
        $nodeId1 = 'id1';
        $nodeId2 = 'id2';
        $nodeId3 = 'id3';
        $node1 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(100)
            ->shouldReceive('getId')
            ->andReturn($nodeId1)
            ->getMock();
        $node2 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(149)
            ->shouldReceive('getId')
            ->andReturn($nodeId2)
            ->getMock();
        $node3 = m::mock(Node::class)
            ->shouldReceive('getJobCount')
            ->andReturn(151)
            ->shouldReceive('getId')
            ->andReturn($nodeId3)
            ->getMock();

        $nodes = [
            $nodeId1 => $node1,
            $nodeId2 => $node2
        ];
        $expectedNodes = $nodes;
        $p = new ConservativeJobCountPrioritizer();
        $p->setMarginToSwitch(0.5);
        $resultNodes = $p->sort($nodes, $nodeId1);
        $this->assertSame($expectedNodes, $resultNodes);

        $nodes2 = [
            $nodeId1 => $node1,
            $nodeId3 => $node3
        ];
        $expectedNodes2 = [
            $nodeId3 => $node3,
            $nodeId1 => $node1
        ];
        $resultNodes2 = $p->sort($nodes2, $node1);
        $this->assertSame($expectedNodes2, $resultNodes2);
    }



}
