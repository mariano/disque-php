<?php
namespace Disque\Command\Response;

class StringResponse extends BasicTypeResponse implements ResponseInterface
{
    /**
     * Basic data type for this response
     *
     * @var int
     */
    protected $type = self::TYPE_STRING;

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