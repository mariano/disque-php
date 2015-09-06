<?php
namespace Disque\Connection;

/**
 * Identify a Disque server we can connect to
 *
 * @package Disque\Connection
 */
class Credentials
{
    /**
     * A sprintf format for creating a node address
     */
    const ADDRESS_FORMAT = '%s:%d';

    /**
     * @var string A Disque server host or IP address
     */
    private $host;
    
    /**
     * @var int The server port
     */
    private $port;
    
    /**
     * @var string|null The password if set for this server
     */
    private $password;
    
    /**
     * @var int|null The maximum seconds to wait for a connection to the server
     */
    private $connectionTimeout;
    
    /**
     * @var int|null The maximum seconds to wait for a response from the server
     */
    private $responseTimeout;

    /**
     * @param string      $host
     * @param int         $port
     * @param string|null $password
     * @param int|null    $connectionTimeout
     * @param int|null    $responseTimeout
     */
    public function __construct(
        $host,
        $port,
        $password = null,
        $connectionTimeout = null,
        $responseTimeout = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->connectionTimeout = $connectionTimeout;
        $this->responseTimeout = $responseTimeout;
    }

    /**
     * Get the server host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the server port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get the server address
     * @return string
     */
    public function getAddress()
    {
        return sprintf(self::ADDRESS_FORMAT, $this->host, $this->port);
    }

    /**
     * Get the password needed to connect to the server
     *
     * Passwords in Disque are optional, servers should be secured in other ways
     *
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Check if the credentials have a password set
     *
     * @return bool
     */
    public function havePassword()
    {
        return !empty($this->password);
    }

    /**
     * Get the maximum time in seconds to wait for a server connection
     *
     * @return int|null
     */
    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    /**
     * Get the maximum time in seconds to wait for any server response
     *
     * @return int|null
     */
    public function getResponseTimeout()
    {
        return $this->responseTimeout;
    }
}
