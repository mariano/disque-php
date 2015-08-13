<?php
namespace Disque\Command\Response;

/**
 * Parse a Disque response that contains the queue, job ID and job body
 */
class JobsWithQueueResponse extends JobsResponse implements ResponseInterface
{
    const KEY_QUEUE = 'queue';

    public function __construct()
    {
        parent::__construct();
        $this->jobDetails = array_merge([self::KEY_QUEUE], $this->jobDetails);
    }
}
