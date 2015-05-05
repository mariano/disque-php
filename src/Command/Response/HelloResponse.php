<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;
use Disque\Exception\InvalidCommandResponseException;

class HelloResponse extends BaseResponse implements ResponseInterface
{
    use ArrayChecker;

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function setBody($body)
    {
        if (!$this->checkFixedArray($body, 3, true)) {
            throw new InvalidCommandResponseException($this->command, $body);
        }
        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @param array $body Response body
     * @return array Parsed response
     * @throws InvalidCommandResponseException
     */
    public function parse()
    {
        $nodes = [];
        foreach (array_slice($this->body, 2) as $node) {
            if (!$this->checkFixedArray($node, 4)) {
                throw new InvalidCommandResponseException($this->command, $this->body);
            }

            $nodes[] = [
                'id' => $node[0],
                'host' => $node[1],
                'port' => $node[2],
                'version' => $node[3]
            ];
        }

        return [
            'version' => $this->body[0],
            'id' => $this->body[1],
            'nodes' => $nodes
        ];
    }

}