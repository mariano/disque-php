<?php
namespace Disque\Command;

interface CommandInterface
{
    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand();

    /**
     * Get processed arguments for command
     *
     * @return array Arguments
     */
    public function getArguments();

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @return void
     * @throws Disque\Command\Argument\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments);

    /**
     * Parse response
     *
     * @param mixed $body Response body
     * @return mixed Parsed response
     * @throws Disque\Command\Response\InvalidResponseException
     */
    public function parse($body);
}