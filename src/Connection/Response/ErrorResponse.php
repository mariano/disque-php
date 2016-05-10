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
        $error = (string) $this->data;
        $exceptionClass = $this->getExceptionClass($error);
        return new $exceptionClass($error);
    }

    /**
     * Creates ResponseException based off error
     *
     * @param string $error Error
     * @return string Class Name
     */
    private function getExceptionClass($error)
    {
        $errors = [
            'PAUSED' => QueuePausedResponseException::class
        ];
        $exceptionClass = ResponseException::class;
        list($errorCode) = explode(" ", $error);
        if (!empty($errorCode) && isset($errors[$errorCode])) {
            $exceptionClass = $errors[$errorCode];
        }
        return $exceptionClass;
    }
}