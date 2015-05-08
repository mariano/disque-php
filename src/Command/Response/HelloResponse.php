<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

class HelloResponse extends BaseResponse implements ResponseInterface
{
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
        foreach (array_slice($this->body, 2) as $node) {
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