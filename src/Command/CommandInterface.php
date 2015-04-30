<?php
namespace Disque\Command;

interface CommandInterface
{
    /**
     * Set arguments for command
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments);

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return array Command (separated in parts)
     */
    public function build();

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return mixed Parsed response
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response);
}