<?php
namespace Disque\Command\Response;

use Disque\Exception\InvalidCommandResponseException;

class IntResponse extends BaseResponse implements ResponseInterface
{
    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @throws InvalidCommandResponseException
     */
    public function setBody($body)
    {
        if (!is_numeric($body)) {
            throw new InvalidCommandResponseException($this->command, $body);
        }
        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws InvalidCommandResponseException
     */
    public function parse()
    {
        return (int) $this->body;
    }
}