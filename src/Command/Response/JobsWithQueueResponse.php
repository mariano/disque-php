<?php
namespace Disque\Command\Response;

class JobsWithQueueResponse extends JobsResponse implements ResponseInterface
{
    /**
     * Create
     */
    public function __construct()
    {
        parent::__construct(true);
    }
}