<?php
namespace Disque\Command;

use Disque\Exception;

class AddJob extends BaseCommand implements CommandInterface
{
    /**
     * Available command options
     *
     * @var array
     */
    protected $options = [
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

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $commandArguments = [
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
     * @return array|null Modified arguments (null to leave as-is)
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
     * @return array Command (separated in parts)
     */
    public function build()
    {
        $options = $this->arguments[0] + $this->options;
        return array_merge(
            ['ADDJOB', $options['queue'], $options['job'], $options['timeout']],
            $this->optionsToArguments($options)
        );
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