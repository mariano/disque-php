<?php
namespace Disque\Command;

use Disque\Exception;

class Hello extends BaseCommand implements CommandInterface
{
    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return string Command for Disque
     */
    public function __toString()
    {
        return 'HELLO';
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
        if (
            !is_array($response) ||
            empty($response) || count($response) < 3 ||
            empty($response[0]) || empty($response[1]) || empty($response[2])
        ) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }

        $nodes = [];
        foreach (array_slice($response, 2) as $node) {
            if (
                count($node) !== 4 || empty($node[0]) || !isset($node[1]) ||
                !isset($node[2]) || empty($node[3])
            ) {
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