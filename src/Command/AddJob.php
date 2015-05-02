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
    protected $arguments = [
        'replicate' => 'REPLICATE',
        'delay' => 'DELAY',
        'retry' => 'RETRY',
        'ttl' => 'TTL',
        'maxlen' => 'MAXLEN',
        'async' => 'ASYNC'
    ];

    /**
     * This command, with all its arguments, ready to be sent to Disque
     *
     * @param array $arguments Arguments
     * @return array Command (separated in parts)
     */
    public function build(array $arguments)
    {
        $count = count($arguments);
        if (!$this->checkFixedArray($arguments, 2, true) || $count > 3) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        } elseif (!is_string($arguments[0]) || !is_string($arguments[1])) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        } elseif ($count === 3 && (!isset($arguments[2]) || !is_array($arguments[2]) || empty($arguments[2]))) {
            throw new Exception\InvalidCommandArgumentException($this, $arguments);
        }

        $options = (!empty($arguments[2]) ? $arguments[2] : []) + ['timeout' => $this->options['timeout']];
        foreach (['timeout', 'replicate', 'delay', 'retry', 'ttl', 'maxlen'] as $intOption) {
            if (isset($options[$intOption]) && !is_int($options[$intOption])) {
                throw new Exception\InvalidCommandOptionException($this, $arguments[2]);
            }
        }

        return array_merge(
            ['ADDJOB', $arguments[0], $arguments[1], $options['timeout']],
            $this->toArguments(array_diff_key($options, ['timeout'=>null]))
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
        } elseif (!is_string($response)) {
            throw new Exception\InvalidCommandResponseException($this, $response);
        }
        return (string) $response;
    }
}