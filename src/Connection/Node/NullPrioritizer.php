<?php
namespace Disque\Connection\Node;

/**
 * This prioritizer always advises to stay on the current node
 */
class NullPrioritizer implements NodePrioritizerInterface
{
    /**
     * @inheritdoc
     */
    public function sort(array $nodes, $currentNodeId)
    {
        if (current($nodes) === $nodes[$currentNodeId]) {
            return $nodes;
        }

        // Move the current node to the first place
        $currentNode = $nodes[$currentNodeId];
        unset($nodes[$currentNodeId]);
        array_unshift($nodes, $currentNode);

        return $nodes;
    }
}
