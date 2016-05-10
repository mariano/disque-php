<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

abstract class CursorResponse extends BaseResponse implements ResponseInterface
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
        if (
            !$this->checkFixedArray($body, 2) ||
            !is_numeric($body[0]) ||
            !is_array($body[1])
        ) {
            throw new InvalidResponseException($this->command, $body);
        }

        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @return array Parsed response. Indexed array with `finished', 'nextCursor`, `queues`
     */
    public function parse()
    {
        $nextCursor = (int) $this->body[0];
        return array_merge([
            'finished' => (0 === $nextCursor),
            'nextCursor' => $nextCursor,
        ], $this->parseBody((array) $this->body[1]));
    }

    /**
     * Parse main body
     *
     * @param array $body Body
     * @return array Parsed body
     */
    abstract protected function parseBody(array $body);
}