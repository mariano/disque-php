<?php
namespace Disque\Connection\Response;

class ErrorResponse extends BaseResponse
{
    /**
     * Parse response
     *
     * @return ResponseException Response
     */
    public function parse()
    {
        return new ResponseException((string) $this->data);
    }
}