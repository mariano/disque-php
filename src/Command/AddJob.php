<?php
namespace Disque\Command;

use Disque\Exception;

class AddJob extends BaseCommand implements CommandInterface
{
    private $options = [
        'queue' => null,
        'job' => null,
        'timeout' => 0,
        'replicate' => null,
        'delay' => null,
        'retry' => null,
        'ttl' => null,
        'maxlen' => null,
        'async' => false
    ];

    private $commandArguments = [
        'REPLICATE' => 'replicate',
        'DELAY' => 'delay',
        'RETRY' => 'retry',
        'TTL' => 'ttl',
        'MAXLEN' => 'maxlen',
        'ASYNC' => 'async'
    ];

    /**
     * Validate the given arguments
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    protected function validate(array $arguments)
    {
        if (count($arguments) !== 1 || !isset($arguments[0])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        if (!isset($arguments[0]['queue']) || !isset($arguments[0]['job'])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments[0]);
        }
    }

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @return string Command for Disque
     */
    public function __toString()
    {
        $options = $this->arguments[0] + $this->options;
        $command = 'ADDJOB ' . $options['queue'] . ' ' . $options['job'] . ' ' . $options['timeout'];

        foreach($this->commandArguments as $argument => $option) {
            if (!isset($options[$option]) || $options[$option] === false) {
                continue;
            }

            $command .= $argument;
            if (!is_bool($options[$option])) {
                $command .= ' ' . $options[$option];
            }
        }

        return $command;
    }

    /**
     * Parse response
     *
     * @param mixed $response Response
     * @return string|null Job ID
     * @throws Disque\Exception\InvalidCommandResponseException
     */
    public function parse($response)
    {
        if ($response === false) {
            return null;
        }

        return (string) $response;
    }
}