<?php
namespace Disque\Connection;

abstract class BaseConnection implements ConnectionInterface
{
    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * Port
     *
     * @var int
     */
    protected $port;

    /**
     * Create a new connection defaulting to localhost:7711
     *
     * @param string $host Host
     * @param int $port Port
     */
    public function __construct($host = 'localhost', $port = 7711)
    {
        $this->setHost($host);
        $this->setPort($port);
    }

    /**
     * Make sure the connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @inheritdoc
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @inheritdoc
     */
    public function connect($connectionTimeout = null, $responseTimeout = null)
    {
        if (!isset($this->host) || !is_string($this->host) || !isset($this->port) || !is_int($this->port)) {
            throw new ConnectionException('Invalid host or port specified');
        }
    }
}
