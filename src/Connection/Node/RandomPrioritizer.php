<?php
namespace Disque\Connection\Node;

/**
 * This prioritizer advises the Manager to switch nodes randomly
 *
 * It can be used to test the availability of nodes in a cluster.
 */
class RandomPrioritizer implements NodePrioritizerInterface
{
    /**
     * @inheritdoc
     */
    public function sort(array $nodes, $currentNodeId)
    {
        if (count($nodes) === 1) {
            return $nodes;
        }

        $nodeIds = array_keys($nodes);
        shuffle($nodeIds);

        $shuffledNodes = [];
        foreach ($nodeIds as $nodeId) {
            $shuffledNodes[$nodeId] = $nodes[$nodeId];
        }

        return $shuffledNodes;
    }
}
