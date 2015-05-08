<?php
namespace Disque\Command\Response;

class KeyValueResponse extends BaseResponse implements ResponseInterface
{
    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        if ($body !== false && (empty($body) || !is_array($body) || (count($body) % 2) !== 0)) {
            throw new InvalidResponseException($this->command, $body);
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
        if ($this->body === false) {
            return null;
        }

        $result = [];
        $key = null;
        foreach ($this->body as $value) {
            if (!is_null($key)) {
                $result[$key] = $value;
                $key = null;
            } else {
                $key = $value;
            }
        }

        return $result;
    }
}