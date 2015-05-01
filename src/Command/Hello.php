<?php
namespace Disque\Command;

use Disque\Exception;

class Hello extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        if (!empty($arguments)) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }
        return ['HELLO'];
    }

    /**
     * Parse response
     *
     * @param array $response Response
     * @return array Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if (!$this->checkFixedArray($response, 3, true)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $nodes = [];
        foreach (array_slice($response, 2) as $node) {
            if (!$this->checkFixedArray($node, 4)) {
                throw new Exception\InvalidCommandResponseException($this, $response);
            }

            $nodes[] = [
                'id' => $node[0],
                'host' => $node[1],
                'port' => $node[2],
                'version' => $node[3]
            ];
        }

        return [
            'version' => $response[0],
            'id' => $response[1],
            'nodes' => $nodes
        ];
    }
}