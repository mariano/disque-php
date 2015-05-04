<?php
namespace Disque\Command;

use Disque\Exception;

class AddJob extends BaseCommand implements CommandInterface
{
    /**
     * Tells the response type for this command
     *
     * @var int
     */
    protected $responseType = self::RESPONSE_TYPE_STRING;

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
    protected $availableArguments = [
        'replicate' => 'REPLICATE',
        'delay' => 'DELAY',
        'retry' => 'RETRY',
        'ttl' => 'TTL',
        'maxlen' => 'MAXLEN',
        'async' => 'ASYNC'
    ];

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'ADDJOB';
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @throws Disque\Exception\InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
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
                throw new Exception\InvalidCommandOptionException($this, (array) $arguments[2]);
            }
        }

        $this->arguments = array_merge(
            [$arguments[0], $arguments[1], $options['timeout']],
            $this->toArguments(array_diff_key($options, ['timeout'=>null]))
        );
    }
}