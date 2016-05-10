<?php
namespace Disque\Command\Response;

class QscanResponse extends CursorResponse implements ResponseInterface
{
    /**
     * Parse main body
     *
     * @param array $body Body
     * @return array Parsed body
     */
    protected function parseBody(array $body)
    {
        return ['queues' => $body];
    }
}