<?php
namespace Disque\Command;

use Disque\Command\Argument\ArrayChecker;
use Disque\Command\Argument\OptionChecker;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\Response\CursorResponse;

class QScan extends BaseCommand implements CommandInterface
{
    use ArrayChecker;
    use OptionChecker;

    /**
     * Tells which class handles the response
     *
     * @var int
     */
    protected $responseHandler = CursorResponse::class;

    /**
     * Available command options
     *
     * @var array
     */
    protected $options = [
        'busyloop' => false,
        'count' => null,
        'minlen' => null,
        'maxlen' => null,
        'importrate' => null
    ];

    /**
     * Available command arguments, and their mapping to options
     *
     * @var array
     */
    protected $availableArguments = [
        'busyloop' => 'BUSYLOOP',
        'count' => 'COUNT',
        'minlen' => 'MINLEN',
        'maxlen' => 'MAXLEN',
        'importrate' => 'IMPORTRATE'
    ];

    /**
     * Get command
     *
     * @return string Command
     */
    public function getCommand()
    {
        return 'QSCAN';
    }

    /**
     * Set arguments for the command
     *
     * @param array $arguments Arguments
     * @throws InvalidOptionException
     */
    public function setArguments(array $arguments)
    {
        $options = [];
        if (!empty($arguments)) {
            if (
                !$this->checkFixedArray($arguments, count($arguments) > 1 ? 2 : 1) ||
                !is_numeric($arguments[0]) ||
                (isset($arguments[1]) && !is_array($arguments[1]))
            ) {
                throw new InvalidCommandArgumentException($this, $arguments);
            }

            if (isset($arguments[1])) {
                $options = $arguments[1];
                $this->checkOptionsInt($options, ['cursor', 'count', 'minlen', 'maxlen', 'importrate']);
            }
        }

        $this->arguments = array_merge([
            !empty($arguments) ? (int) $arguments[0] : 0
        ], $this->toArguments($options));
    }
}