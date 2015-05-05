<?php
namespace Disque\Command\Response;

use Disque\Exception\InvalidCommandResponseException;

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
     * @throws InvalidCommandResponseException
     */
    public function setBody($body)
    {
        $error = false;
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
            throw new InvalidCommandResponseException($this->command, $body);
        }
        parent::setBody($body);
    }
}