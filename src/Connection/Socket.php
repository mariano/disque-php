<?php
namespace Disque\Connection;

use Exception;
use Disque\Command\CommandInterface;
use Disque\Connection\Response\ResponseException;
use Disque\Connection\Response;

/**
 * This class is greatly inspired by `Predis\Connection\StreamConnection`,
 * which is part of [predis](https://github.com/nrk/predis) and was developed
 * by Daniele Alessandri <suppakilla@gmail.com>. All credits go to him where
 * relevant.
 */
class Socket extends BaseConnection implements ConnectionInterface
{
    /**
     * Socket handle
     *
     * @var resource
     */
    protected $socket;

    /**
     * Response handlers
     *
     * @var array
     */
    private $responseHandlers = [
        '+' => Response\StringResponse::class,
        '-' => Response\ErrorResponse::class,
        ':' => Response\IntResponse::class,
        '$' => Response\TextResponse::class,
        '*' => Response\ArrayResponse::class
    ];

    /**
     * Connect
     *
     * @param array $options Connection options
     * @throws ConnectionException
     */
    public function connect(array $options = [])
    {
        parent::connect($options);

        $options += [
            'timeout' => null,
            'streamTimeout' => null
        ];

        $this->socket = $this->getSocket($this->host, $this->port, (float) $options['timeout']);
        if (!is_resource($this->socket)) {
            throw new ConnectionException("Could not connect to {$this->host}:{$this->port}");
        }

        stream_set_blocking($this->socket, 1);
        if (!is_null($options['streamTimeout'])) {
            stream_set_timeout($this->socket, $options['streamTimeout']);
        }
    }

    /**
     * Disconnect
     */
    public function disconnect()
    {
        if (!$this->isConnected()) {
            return;
        }
        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Tells if connection is established
     *
     * @return bool Success
     */
    public function isConnected()
    {
        return (isset($this->socket) && is_resource($this->socket));
    }

    /**
     * Execute command, and get response
     *
     * @param CommandInterface $command
     * @return mixed Response
     * @throws ConnectionException
     */
    public function execute(CommandInterface $command)
    {
        $commandName = $command->getCommand();
        $arguments = $command->getArguments();
        $totalArguments = count($arguments);

        $parts = [
            '*' . ($totalArguments + 1),
            '$' . strlen($commandName),
            $commandName
        ];

        for ($i=0; $i < $totalArguments; $i++) {
            $argument = $arguments[$i];
            $parts[] = '$' . strlen($argument);
            $parts[] = $argument;
        }

        $this->send(implode("\r\n", $parts)."\r\n");
        return $this->receive($command->isBlocking());
    }

    /**
     * Execute a command on the connection
     *
     * @param string $data Data to send
     * @throws ConnectionException
     */
    public function send($data)
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('No connection established');
        }

        if (!is_string($data)) {
            throw new ConnectionException('Invalid data to be sent to client');
        } elseif ($data === '') {
            return;
        }

        do {
            $length = strlen($data);
            $bytes = fwrite($this->socket, $data);
            if (empty($bytes)) {
                throw new ConnectionException("Could not write {$length} bytes to client");
            } elseif ($bytes === $length) {
                break;
            }

            $data = substr($data, $bytes);
        } while ($length > 0);
    }

    /**
     * Read data from connection
     *
     * @param bool $keepWaiting If `true`, timeouts on stream read will be ignored
     * @return mixed Data received
     * @throws ConnectionException
     * @throws ResponseException
     */
    public function receive($keepWaiting = false)
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('No connection established');
        }

        $type = $this->getType($keepWaiting);
        if (!array_key_exists($type, $this->responseHandlers)) {
            throw new ResponseException("Don't know how to handle a response of type {$type}");
        }

        $responseHandlerClass = $this->responseHandlers[$type];
        $responseHandler = new $responseHandlerClass($this->getData());
        $responseHandler->setReader(function ($bytes) {
            return fread($this->socket, $bytes);
        });
        $responseHandler->setReceiver(function () use ($keepWaiting) {
            return $this->receive($keepWaiting);
        });
        $response = $responseHandler->parse();
        if ($response instanceof Exception) {
            throw $response;
        }

        return $response;
    }

    /**
     * Build actual socket
     *
     * @param string $host Host
     * @param int $port Port
     * @param float $timeout Timeout
     * @return resource Socket
     */
    protected function getSocket($host, $port, $timeout)
    {
        return stream_socket_client("tcp://{$host}:{$port}", $error, $message, $timeout, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
    }

    /**
     * Get the first byte from Disque, which contains the data type
     *
     * @param bool $keepWaiting If `true`, timeouts on stream read will be ignored
     * @return string A single char
     * @throws ConnectionException
     */
    private function getType($keepWaiting = false)
    {
        $type = null;
        while (!feof($this->socket)) {
            $type = fgetc($this->socket);
            if ($type !== false && $type !== '') {
                break;
            }

            $info = stream_get_meta_data($this->socket);
            if (!$keepWaiting || !$info['timed_out']) {
                break;
            }
        }

        if ($type === false || $type === '') {
            throw new ConnectionException('Nothing received while reading from client');
        }

        return $type;
    }

    /**
     * Get a line of data
     *
     * @return string Line of data
     * @throws ConnectionException
     */
    private function getData()
    {
        $data = fgets($this->socket);
        if ($data === false || $data === '') {
            throw new ConnectionException('Nothing received while reading from client');
        }
        return $data;
    }
}