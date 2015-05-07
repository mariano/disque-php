<?php
namespace Disque\Connection;

use Disque\Command\CommandInterface;
use Disque\Connection\Exception\ConnectionException;
use Disque\Connection\Exception\ResponseException;

/**
 * This class is greatly inspired by `Predis\Connection\StreamConnection`,
 * which is part of [predis](https://github.com/nrk/predis) and was developed
 * by Daniele Alessandri <suppakilla@gmail.com>. All credits go to him where
 * relevant.
 */
class Connection extends BaseConnection implements ConnectionInterface
{
    const READ_BUFFER_LENGTH = 8192;

    /**
     * Socket handle
     *
     * @var resource
     */
    protected $socket;

    /**
     * Connect
     *
     * @param array $options Connection options
     * @throws Disque\Connection\Exception\ConnectionException
     */
    public function connect(array $options = [])
    {
        parent::connect($options);

        $options += [
            'timeout' => null,
            'streamTimeout' => null
        ];

        $this->socket = stream_socket_client("tcp://{$this->host}:{$this->port}", $error, $message, (float) $options['timeout'], STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT);
        if (!is_resource($this->socket)) {
            throw new ConnectionException("Could not connect to {$this->host}:{$this->port}: #{$error} ({$message})");
        }

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
     * @throws Disque\Connection\Exception\ConnectionException
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
        return $this->receive();
    }

    /**
     * Execute a command on the connection
     *
     * @param string $data Data to send
     * @throws Disque\Connection\Exception\ConnectionException
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
     * @return mixed Data received
     * @throws Disque\Connection\Exception\ConnectionException
     * @throws Disque\Connection\Exception\ResponseException
     */
    public function receive()
    {
        if (!$this->isConnected()) {
            throw new ConnectionException('No connection established');
        }

        $type = fgetc($this->socket);
        if ($type === false || $type === '') {
            throw new ConnectionException('Nothing received while reading from client');
        }

        $data = fgets($this->socket);
        if ($data === false || $data === '') {
            throw new ConnectionException('Nothing received while reading from client');
        }

        $data = substr($data, 0, -2); // Get rid of last CRLF

        switch ($type) {
            case '+':
                return (string) $data;
            case '-':
                $data = (string) $data;
                throw new ResponseException("Error received from client: {$data}");
            case ':':
                return (int) $data;
            case '$':
                $bytes = (int) $data;
                if ($bytes < 0) {
                    return null;
                }

                $bytes += 2; // CRLF
                $string = '';

                do {
                    $buffer = fread($this->socket, min($bytes, self::READ_BUFFER_LENGTH));
                    if ($buffer === false || $buffer === '') {
                        throw new ConnectionException('Error while reading buffered string from client');
                    }
                    $string .= $buffer;
                    $bytes -= strlen($buffer);
                } while ($bytes > 0);

                return substr($string, 0, -2); // Remove last CRLF
            case '*':
                $count = (int) $data;
                if ($count < 0) {
                    return null;
                }

                $elements = [];
                for ($i=0; $i < $count; $i++) {
                    $elements[$i] = $this->receive();
                }
                return $elements;
        }

        throw new ResponseException("Don't know how to handle a response of type {$type}");
    }
}