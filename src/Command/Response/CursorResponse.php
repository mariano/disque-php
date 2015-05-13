<?php
namespace Disque\Command\Response;

use Disque\Command\Argument\ArrayChecker;

class CursorResponse extends BaseResponse implements ResponseInterface
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
        return [
            'finished' => (0 === $nextCursor),
            'nextCursor' => $nextCursor,
            'queues' => (array) $this->body[1]
        ];
    }
}