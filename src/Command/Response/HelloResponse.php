<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

class HelloResponse extends BaseResponse implements ResponseInterface
{
    /**
     * Array keys
     */
    const NODE_ID = 'id';
    const NODE_HOST = 'host';
    const NODE_PORT = 'port';
    const NODE_VERSION = 'version';
    const NODES = 'nodes';

    /**
     * Position indexes in the Disque response
     */
    const POS_VERSION = 0;
    const POS_ID = 1;
    const POS_NODES_START = 2;

    const POS_NODE_ID = 0;
    const POS_NODE_HOST = 1;
    const POS_NODE_PORT = 2;
    const POS_NODE_VERSION = 3;

    use ArrayChecker;

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        if (!$this->checkFixedArray($body, 3, true)) {
            throw new InvalidResponseException($this->command, $body);
        }
        foreach (array_slice($body, 2) as $node) {
            if (!$this->checkFixedArray($node, 4)) {
                throw new InvalidResponseException($this->command, $body);
            }
        }
        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @return array Parsed response
     */
    public function parse()
    {
        $nodes = [];
        foreach (array_slice($this->body, self::POS_NODES_START) as $node) {
            $nodes[] = [
                self::NODE_ID => $node[self::POS_NODE_ID],
                self::NODE_HOST => $node[self::POS_NODE_HOST],
                self::NODE_PORT => $node[self::POS_NODE_PORT],
                self::NODE_VERSION => $node[self::POS_NODE_VERSION]
            ];
        }

        return [
            self::NODE_VERSION => $this->body[self::POS_VERSION],
            self::NODE_ID => $this->body[self::POS_ID],
            self::NODES => $nodes
        ];
    }

}
