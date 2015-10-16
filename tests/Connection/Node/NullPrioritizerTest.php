<?php
namespace Disque\Test\Connection\Node;

use Disque\Connection\Node\NullPrioritizer;
use Mockery as m;

class NullPrioritizerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $p = new NullPrioritizer();
        $this->assertInstanceOf(NullPrioritizer::class, $p);
    }

    public function testSortFirstNode()
    {
        $nodeId1 = 'id1';
        $nodeId2 = 'id2';
        $nodes = [
            $nodeId1 => $nodeId1,
            $nodeId2 => $nodeId2
        ];
        $nodesExpected = [
            $nodeId1 => $nodeId1,
            $nodeId2 => $nodeId2
        ];
        $p = new NullPrioritizer();
        $nodesResult = $p->sort($nodes, $nodeId1);
        $this->assertSame($nodesExpected, $nodesResult);
    }

    public function testSortSecondNode()
    {
        $nodeId1 = 'id1';
        $nodeId2 = 'id2';
        $nodes = [
            $nodeId2 => $nodeId2,
            $nodeId1 => $nodeId1
        ];
        $nodesExpected = [
            $nodeId1 => $nodeId1,
            $nodeId2 => $nodeId2
        ];
        $p = new NullPrioritizer();
        $nodesResult = $p->sort($nodes, $nodeId1);
        $this->assertSame(array_values($nodesExpected), array_values($nodesResult));
    }
}
