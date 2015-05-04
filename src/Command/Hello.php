<?php
namespace Disque\Command;

use Disque\Exception;

class Hello extends BaseCommand implements CommandInterface
{
    /**
     * Tells the argument types for this command
     *
     * @var int
     */
    protected $argumentsType = self::ARGUMENTS_TYPE_EMPTY;

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
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