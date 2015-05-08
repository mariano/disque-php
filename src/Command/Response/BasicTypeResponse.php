<?php
namespace Disque\Command\Response;

abstract class BasicTypeResponse extends BaseResponse implements ResponseInterface
{
    const TYPE_STRING = 0;
    const TYPE_INT = 1;

    /**
     * Basic data type for this response
     *
     * @var int
     */
    protected $type;

    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        switch ($this->type) {
            case self::TYPE_INT:
                $error = !is_numeric($body);
                break;
            case self::TYPE_STRING:
            default:
                $error = !is_string($body);
                break;
        }
        if ($error) {
            throw new InvalidResponseException($this->command, $body);
        }
        parent::setBody($body);
    }
}