<?php
namespace Disque\Command\Response;

use Disque\Exception\InvalidCommandResponseException;

class StringResponse extends BaseResponse implements ResponseInterface
{
    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidCommandResponseException
     */
    public function setBody($body)
    {
        if (!is_string($body)) {
            throw new InvalidCommandResponseException($this->command, $body);
        }
        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * @return string Parsed response
     * @throws InvalidCommandResponseException
     */
    public function parse()
    {
        return (string) $this->body;
    }
}