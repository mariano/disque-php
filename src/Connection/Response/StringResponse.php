<?php
namespace Disque\Connection\Response;

class StringResponse extends BaseResponse
{
    /**
     * Parse response
     *
     * @return string Response
     */
    public function parse()
    {
        return (string) $this->data;
    }
}