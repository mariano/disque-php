<?php
namespace Disque\Command\Response;

class JscanResponse extends CursorResponse implements ResponseInterface
{
    /**
     * Parse main body
     *
     * @param array $body Body
     * @return array Parsed body
     */
    protected function parseBody(array $body)
    {
        $jobs = [];
        if (!empty($body)) {
            if (is_string($body[0])) {
                foreach ($body as $element) {
                    $jobs[] = ['id' => $element];
                }
            } else {
                $keyValueResponse = new KeyValueResponse();
                $keyValueResponse->setCommand($this->command);

                foreach ($body as $element) {
                    $keyValueResponse->setBody($element);
                    $jobs[] = $keyValueResponse->parse();
                }
            }
        }
        return ['jobs' => $jobs];
    }
}