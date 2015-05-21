<?php
namespace Disque\Connection\Response;

class ArrayResponse extends BaseResponse
{
    /**
     * Parse response
     *
     * @return string Response
     * @throws ConnectionException
     */
    public function parse()
    {
        $count = (int) $this->data;
        if ($count < 0) {
            return null;
        }

        $elements = [];
        for ($i=0; $i < $count; $i++) {
            $elements[$i] = call_user_func($this->receiver);
        }
        return $elements;
    }
}