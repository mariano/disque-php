<?php
namespace Disque\Connection\Node;

use Traversable;

/**
 * Sort Disque nodes by priority in order to connect to the most useful one
 *
 * The Connection\Manager accepts an implementation of this interface and asks
 * it to sort nodes by priority. It then tries to connect to the best available
 * node. The library provides a default implementation.
 *
 * In order to make a decision, Nodes have a resettable jobCount,
 * a totalJobCount and can be extended to accept more markers, eg. latency etc.
 */
interface NodePrioritizerInterface
{
    /**
     * Sort the nodes by priority
     * 
     * @param Node[] $nodes         A list of known Disque nodes indexed
     *                              by a node ID
     * @param string $currentNodeId Node ID of the currently connected node
     *
     * @return Traversable|Node[] A list of nodes sorted by priority
     */
    public function sort(array $nodes, $currentNodeId);
}
