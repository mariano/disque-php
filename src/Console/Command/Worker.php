<?php
namespace Disque\Console\Command;

use Disque\Client;
use Disque\Connection\Credentials;
use Disque\Console\HandlerInterface;
use Disque\Queue\JobInterface;
use Disque\Queue\Marshal\MarshalerInterface;
use Disque\Queue\Queue;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Yaml\Parser;

class Worker extends Command
{
    const DEFAULT_PERIOD = 500;

    /**
     * Output stream
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Wether to stop waiting for jobs
     *
     * @var bool
     */
    private $stop;

    /**
     * How many ms to wait for a job to arrive
     *
     * @var int
     */
    private $period;

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('worker:work')
            ->setDescription('Run a worker instance')
            ->addArgument(
                'configuration',
                InputArgument::REQUIRED,
                'Configuration file'
            )
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source to use for pulling jobs (should be defined in configuration file)'
            );
    }

    /**
     * Initializes the command just after the input has been validated.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        foreach (['pcntl_signal'] as $function) {
            if (!function_exists($function)) {
                throw new Exception("I need function {$function} to work");
            }
        }

        $output->getFormatter()->setStyle('date', new OutputFormatterStyle(
            'cyan'
        ));
        $output->getFormatter()->setStyle('id', new OutputFormatterStyle(
            'yellow'
        ));
        $output->getFormatter()->setStyle('body', new OutputFormatterStyle(
            'yellow', null, ['bold']
        ));
        $output->getFormatter()->setStyle('success', new OutputFormatterStyle(
            'green', null, ['bold']
        ));
        $output->getFormatter()->setStyle('error', new OutputFormatterStyle(
            'red', null, ['bold']
        ));
        $output->getFormatter()->setStyle('errorMessage', new OutputFormatterStyle(
            'white', 'red', ['bold']
        ));

        $this->output = $output;

        declare(ticks = 30);

        $onSignal = function ($signal) {
            $this->out("Got signal {$signal}. Shutting down gracefully", OutputInterface::VERBOSITY_VERBOSE);
            $this->stop = true;
        };

        foreach ([SIGTERM, SIGHUP] as $signal) {
            if (!pcntl_signal($signal, $onSignal)) {
                throw new RuntimeException("Could not register signal {$signal}");
            }
        }
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = $this->settings($input->getArgument('configuration'), $input->getArgument('source'));
        $queue = $this->getQueue($settings);
        $handler = (isset($settings['handler']) ? new $settings['handler'] : null);

        $this->out("Starting worker");
        do {
            $this->out("Waiting for jobs on {$settings['queue']}", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $job = $queue->pull($settings['period']);
            if (!isset($job)) {
                $this->out("No jobs available", OutputInterface::VERBOSITY_VERY_VERBOSE);
                continue;
            }

            $this->process($queue, $job, $handler);
        } while (!$this->stop);

        $this->out("Worker finished");
    }

    /**
     * Process job
     *
     * @param Queue $queue Queue where job came from
     * @param JobInterface $job Job
     * @param HandlerInterface $handler Handler
     * @return void
     */
    private function process(Queue $queue, JobInterface $job, HandlerInterface $handler = null)
    {
        $this->out("Got job <id>{$job->getId()}</id> with body <body>" . json_encode($job->getBody()) . "</body>", OutputInterface::VERBOSITY_VERBOSE);
        $this->out("Executing job <id>{$job->getId()}</id>... ", OutputInterface::VERBOSITY_NORMAL, ['newLine' => false]);

        $error = null;
        try {
            if (isset($handler)) {
                $handler->handle($job);
            } elseif (is_callable($job)) {
                call_user_func($job);
            }

            $queue->processed($job);
        } catch (Exception $e) {
            $queue->failed($job);
            $error = $e->getMessage();
        }

        if (!isset($error)) {
            $message = "<success>DONE</success>";
        } else {
            $message = "<error>ERROR</error>: <errorMessage>{$error}</errorMessage>";
        }

        $this->out($message, OutputInterface::VERBOSITY_NORMAL, ['timestamp' => false]);
    }

    /**
     * Get a Disque queue
     *
     * @param array $settings Settings
     * @return Queue Disque queue
     */
    private function getQueue(array $settings)
    {
        $credentials = [];
        foreach ($settings['nodes'] as $node) {
            $credentials[] = new Credentials($node['host'], $node['port'], $node['password'], $node['timeoutConnect'], $node['timeoutResponse']);
        }

        $client = new Client($credentials);
        $queue = $client->queue($settings['queue']);
        if (isset($settings['marshaller'])) {
            $queue->setMarshaler(new $settings['marshaller']);
        }

        return $queue;
    }

    /**
     * Parse configuration
     *
     * @param string $path Path to configuration
     * @param string $source Source
     * @return array Settings
     * @throws InvalidArgumentException
     */
    private function settings($path, $source)
    {
        if (empty($path) || !is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("No valid configuration file found: " . $path);
        }

        $settings = (new Parser())->parse(file_get_contents($path));
        if (!isset($settings[$source]) || !is_array($settings[$source])) {
            throw new InvalidArgumentException("Source {$source} is not included in configuration file, or it is not valid");
        }

        $settings = array_merge(array_diff_key($settings, [$source => null]), $settings[$source]);

        if (!isset($settings['queue'])) {
            throw new InvalidArgumentException("Configuration file must include a queue");
        } elseif (empty($settings['nodes'])) {
            throw new InvalidArgumentException("No nodes defined in configuration file");
        } elseif (isset($settings['handler']) && !class_exists($settings['handler'])) {
            throw new InvalidArgumentException("No valid class defined in handler setting");
        } elseif (isset($settings['marshaller']) && !class_exists($settings['marshaller'])) {
            throw new InvalidArgumentException("No valid class defined in marshaller setting");
        }

        $settings += [
            'handler' => null,
            'marshaller' => null,
            'period' => self::DEFAULT_PERIOD,
        ];

        if (isset($settings['handler'])) {
            $class = $settings['handler'];
            $settings['handler'] = new $class();
            if (!($settings['handler'] instanceof HandlerInterface)) {
                throw new InvalidArgumentException("Class {$class} does not implement HandlerInterface");
            }
        }

        if (isset($settings['marshaller'])) {
            $class = $settings['marshaller'];
            $settings['marshaller'] = new $class();
            if (!($settings['marshaller'] instanceof MarshalerInterface)) {
                throw new InvalidArgumentException("Class {$class} does not implement MarshalerInterface");
            }
        }

        foreach ($settings['nodes'] as $i => $node) {
            $settings['nodes'][$i] += [
                'host' => '127.0.0.1',
                'port' => 7711,
                'password' => null,
                'timeoutConnect' => null,
                'timeoutResponse' => null
            ];
        }

        return $settings;
    }

    /**
     * Output the given string using the given log level
     *
     * @param string $text Text to output
     * @param int $level Log level of message
     * @param array $settings Settings
     * @return void
     */
    private function out($text, $level = OutputInterface::VERBOSITY_NORMAL, array $settings = [])
    {
        if ($this->output->isQuiet() || $level > $this->output->getVerbosity()) {
            return;
        }

        $settings += [
            'newLine' => true,
            'timestamp' => true
        ];

        $message = ($settings['timestamp'] ? '<date>[' . date('Y-m-d H:i:s') . ']</date> ' : '') . $text;
        if ($settings['newLine']) {
            $this->output->writeln($message);
        } else {
            $this->output->write($message);
        }
    }
}