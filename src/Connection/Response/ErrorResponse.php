<?php
namespace Disque\Connection\Response;

class ErrorResponse extends BaseResponse
{
    const ERRORS = [
        'PAUSED' => QueuePausedResponseException::class
    ];
    /**
     * Parse response
     *
     * @return ResponseException Response
     */
    public function parse()
    {
        $error = (string) $this->data;
        list($errorCode) = explode(" ", $error);
        if (!empty($errorCode) && isset(static::ERRORS[$errorCode])) {
            $exceptionClass = static::ERRORS[$errorCode];
            throw new $exceptionClass($error);
        }
        return new ResponseException($error);
    }
}