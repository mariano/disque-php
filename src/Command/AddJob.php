<?php
namespace Disque\Command;

use Disque\Command\Argument\ArrayChecker;
use Disque\Command\Argument\OptionChecker;
use Disque\Command\Argument\InvalidCommandArgumentException;

class AddJob extends BaseCommand implements CommandInterface
{
    use ArrayChecker;
    use OptionChecker;

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
     * The first two values in the $arguments array must be the queue name and
     * the job body. The third value is optional and if present, must be
     * an array with further arguments.
     * @see $availableArguments
     *
     * @param array $arguments Arguments
     * @throws InvalidCommandArgumentException
     */
    public function setArguments(array $arguments)
    {
        $count = count($arguments);
        if (!$this->checkFixedArray($arguments, 2, true) || $count > 3) {
            throw new InvalidCommandArgumentException($this, $arguments);
        } elseif (!is_string($arguments[0]) || !is_string($arguments[1])) {
            throw new InvalidCommandArgumentException($this, $arguments);
        } elseif ($count === 3 && (!isset($arguments[2]) || !is_array($arguments[2]))) {
            throw new InvalidCommandArgumentException($this, $arguments);
        }

        $options = (!empty($arguments[2]) ? $arguments[2] : []) + ['timeout' => $this->options['timeout']];
        $this->checkOptionsInt($options, ['timeout', 'replicate', 'delay', 'retry', 'ttl', 'maxlen']);

        $this->arguments = array_merge(
            [$arguments[0], $arguments[1], $options['timeout']],
            $this->toArguments(array_diff_key($options, ['timeout'=>null]))
        );
    }
}