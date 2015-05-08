<?php
namespace Disque\Command\Response;

class IntResponse extends BasicTypeResponse implements ResponseInterface
{
    /**
     * Basic data type for this response
     *
     * @var int
     */
    protected $type = self::TYPE_INT;

    /**
     * Parse response
     *
     * @return int Parsed response
     */
    public function parse()
    {
        return (int) $this->body;
    }
}