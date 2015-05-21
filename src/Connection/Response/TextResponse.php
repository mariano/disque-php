<?php
namespace Disque\Connection\Response;

use Disque\Connection\ConnectionException;

class TextResponse extends BaseResponse
{
    const READ_BUFFER_LENGTH = 8192;

    /**
     * Parse response
     *
     * @return string Response
     * @throws ConnectionException
     */
    public function parse()
    {
        $bytes = (int) $this->data;
        if ($bytes < 0) {
            return null;
        }

        $bytes += 2; // CRLF
        $string = '';

        do {
            $buffer = $this->read($bytes);
            $string .= $buffer;
            $bytes -= strlen($buffer);
        } while ($bytes > 0);

        return substr($string, 0, -2); // Remove last CRLF
    }

    /**
     * Read text
     *
     * @param int $bytes Bytes to read
     * @return string Text
     * @throws ConnectionException
     */
    private function read($bytes)
    {
        $buffer = call_user_func($this->reader, min($bytes, self::READ_BUFFER_LENGTH));
        if ($buffer === false || $buffer === '') {
            throw new ConnectionException('Error while reading buffered string from client');
        }
        return $buffer;
    }
}