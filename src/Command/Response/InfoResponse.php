<?php

namespace Disque\Command\Response;

class InfoResponse extends BaseResponse implements ResponseInterface
{
    /**
     * Set response body
     *
     * @param mixed $body Response body
     * @return void
     * @throws InvalidResponseException
     */
    public function setBody($body)
    {
        if ($body !== false && (empty($body) || !is_string($body))) {
            throw new InvalidResponseException($this->command, $body);
        }

        parent::setBody($body);
    }

    /**
     * Parse response
     *
     * Taken from https://github.com/nrk/predis/blob/v1.1/src/Command/ServerInfoV26x.php
     *
     * @return array Parsed response
     */
    public function parse()
    {
        if ($this->body === false || !is_string($this->body)) {
            return null;
        }

        $data = $this->body;
        $info = [];

        $current = null;
        $infoLines = preg_split('/\r?\n/', $data);

        if (isset($infoLines[0]) && $infoLines[0][0] !== '#') {
            return $this->parseSection($data);
        }

        foreach ($infoLines as $row) {
            if ($row === '') {
                continue;
            }

            if (preg_match('/^# (\w+)$/', $row, $matches)) {
                $info[$matches[1]] = [];
                $current = &$info[$matches[1]];
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $current[$k] = $v;
        }

        return $info;
    }

    protected function parseRow($row)
    {
        list($k, $v) = explode(':', $row, 2);
        return [$k, $v];
    }

    protected function parseSection($data)
    {
        $info = [];
        $infoLines = preg_split('/\r?\n/', $data);

        foreach ($infoLines as $row) {
            if (strpos($row, ':') === false) {
                continue;
            }

            list($k, $v) = $this->parseRow($row);
            $info[$k] = $v;
        }

        return $info;
    }
}
