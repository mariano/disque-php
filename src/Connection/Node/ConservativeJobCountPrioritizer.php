<?php
namespace Disque\Connection\Node;

use InvalidArgumentException;

/**
 * A prioritizer switching nodes if they have more jobs by a given margin
 *
 * This class prioritizes nodes by job count and its Disque priority. Because
 * there is a cost to switch, it doesn't switch from the current node unless
 * the new candidate has a safe margin over the current node.
 *
 * This margin can be set manually and defaults to 5%, ie. the new candidate
 * must have 5% more jobs than the current node.
 *
 * This parameter makes the prioritizer behave conservatively - it prefers
 * the status quo and won't switch immediately if the difference is small.
 *
 * You can make the prioritizer eager by setting the margin to 0, or more
 * conservative by setting it higher. Setting the margin to negative values
 * is not allowed.
 */
class ConservativeJobCountPrioritizer implements NodePrioritizerInterface
{
    /**
     * @var float A margin to switch from the current node
     *
     * 0.05 means the new node must have 5% more jobs than the current node
     * in order to recommend switching over.
     */
    private $marginToSwitch = 0.05;

    /**
     * Get the margin to switch
     *
     * @return float
     */
    public function getMarginToSwitch()
    {
        return $this->marginToSwitch;
    }

    /**
     * Set the margin to switch
     *
     * @param float $marginToSwitch A positive float or 0
     *
     * @throws InvalidArgumentException
     */
    public function setMarginToSwitch($marginToSwitch)
    {
        if ($marginToSwitch < 0) {
            throw new InvalidArgumentException('Margin to switch must not be negative');
        }

        $this->marginToSwitch = $marginToSwitch;
    }

    /**
     * @inheritdoc
     */
    public function sort(array $nodes, $currentNodeId)
    {
        // Optimize for a "cluster" consisting of just 1 node - skip everything
        if (count($nodes) === 1) {
            return $nodes;
        }

        uasort($nodes, function(Node $nodeA, Node $nodeB) use ($currentNodeId) {
            $priorityA = $this->calculateNodePriority($nodeA, $currentNodeId);
            $priorityB = $this->calculateNodePriority($nodeB, $currentNodeId);

            if ($priorityA === $priorityB) {
                return 0;
            }

            // Nodes with a higher priority should go first
            return ($priorityA < $priorityB) ? 1 : -1;
        });

        return $nodes;
    }

    /**
     * Calculate the node priority from its job count, stick to the current node
     *
     * As the priority is based on the number of jobs, higher is better.
     *
     * @param Node   $node
     * @param string $currentNodeId
     *
     * @return float Node priority
     */
    private function calculateNodePriority(Node $node, $currentNodeId)
    {
        $priority = $node->getJobCount();

        if ($node->getId() === $currentNodeId) {
            $margin = 1 + $this->marginToSwitch;
            $priority = $priority * $margin;
        }

        // Apply a weight determined by the node priority as assigned by Disque.
        // Priority 1 means the node is healthy.
        // Priority 10 to 100 means the node is probably failing, or has failed
        $disquePriority = $node->getPriority();

        // Disque node priority should never be lower than 1, but let's be sure
        if ($disquePriority < Node::PRIORITY_OK) {
            $disquePriorityWeight = 1;
        } elseif (Node::PRIORITY_OK <= $disquePriority && $disquePriority < Node::PRIORITY_POSSIBLE_FAILURE) {
            // Node is OK, but Disque may have assigned a lower priority to it
            // We use the base-10 logarithm in the formula, so priorities
            // 1 to 10 transform into a weight of 1 to 0.5. When Disque starts
            // using more priority steps, priority 9 will discount about a half
            // of the job count.
            $disquePriorityWeight = 1 / (1 + log10($disquePriority));
        } else {
            // Node is failing, or it has failed
            $disquePriorityWeight = 0;
        }

        $priority = $priority * $disquePriorityWeight;
        return (float) $priority;
    }
}