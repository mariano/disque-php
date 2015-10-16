<?php
namespace Disque\Test\Connection\Node;

use Disque\Connection\Node\Node;
use Disque\Connection\Node\RandomPrioritizer;
use Mockery as m;

class RandomPrioritizerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $p = new RandomPrioritizer();
        $this->assertInstanceOf(RandomPrioritizer::class, $p);
    }

    public function testSort()
    {
        $nodeId1 = 'id1';
        $nodeId2 = 'id2';

        $node1 = m::mock(Node::class);
        $node2 = m::mock(Node::class);

        $nodes = [$nodeId1 => $node1, $nodeId2 => $node2];

        $possibleResults = [
            [$nodeId1 => $node1, $nodeId2 => $node2],
            [$nodeId2 => $node2, $nodeId1 => $node1]
        ];

        $p = new RandomPrioritizer();
        $result = $p->sort($nodes, $nodeId1);
        $this->assertContains($result, $possibleResults);
    }
}
