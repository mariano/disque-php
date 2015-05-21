<?php
namespace Disque\Connection\Response;

class IntResponse extends BaseResponse
{
    /**
     * Parse response
     *
     * @return string Response
     */
    public function parse()
    {
        return (int) $this->data;
    }
}