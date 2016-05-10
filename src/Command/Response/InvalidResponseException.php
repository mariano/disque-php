<?php
namespace Disque\Command\Response;

use Disque\Command\CommandInterface;
use Disque\DisqueException;

class InvalidResponseException extends DisqueException
{
    /**
     * Response
     *
     * @var string
     */
    private $response;

    public function __construct(CommandInterface $command, $response)
    {
        parent::__construct(sprintf("Invalid command response. Command %1s got: %2s",
            get_class($command),
            json_encode($response)
        ));
        $this->response = $response;
    }

    /**
     * Get actual response (if any) that triggered this exception
     *
     * @return string? Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}