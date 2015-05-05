<?php
namespace Disque\Command\Response;

use Disque\Exception\InvalidCommandResponseException;

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