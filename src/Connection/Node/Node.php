<?php
namespace Disque\Connection\Node;

use Disque\Connection\Credentials;
use Disque\Connection\ConnectionInterface;
use Disque\Command\Auth;
use Disque\Command\Hello;
use Disque\Command\Response\HelloResponse;
use Disque\Connection\ConnectionException;
use Disque\Connection\AuthenticationException;
use Disque\Connection\Response\ResponseException;

/**
 * Describe one Disque node, its properties and the connection to it
 */
class Node
{
    /**
     * The response Disque returns if password authentication succeeded
     */
    const AUTH_SUCCESS_MESSAGE = 'OK';

    /**
     * The beginning of a response Disque returns if authentication required
     */
    const AUTH_REQUIRED_MESSAGE = 'NOAUTH';

    /**
     * Node prefix boundaries
     */
    const PREFIX_START = 0;
    const PREFIX_LENGTH = 8;

    /**
     * @var Credentials Credentials of this node - host, port, password
     */
    private $credentials;

    /**
     * @var ConnectionInterface The connection to this node
     */
    private $connection;

    /**
     * @var string Node ID
     */
    private $id;

    /**
     * @var string Node prefix, or the first 8 bytes of the ID
     */
    private $prefix;

    /**
     * @var int Node HELLO reply version (this is not a Disque version)
     * This is an integer, see the Disque source code, functions
     * helloCommand() and addReplyLongLong().
     */
    private $version;

    /**
     * @var array The result of the HELLO command
     *
     * @see Disque\Command\Response\HelloResponse
     */
    private $hello;

    /**
     * @var int The number of jobs from this node since the last counter reset
     *          This counter can be reset, eg. upon a node switch
     */
    private $jobCount = 0;

    /**
     * @var int The number of jobs from this node during its lifetime
     */
    private $totalJobCount = 0;

    public function __construct(Credentials $credentials, ConnectionInterface $connection)
    {
        $this->credentials = $credentials;
        $this->connection = $connection;
    }

    /**
     * Get the node credentials
     *
     * @return Credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Get the node connection
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the node ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the node prefix - the first 8 bytes from the ID
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get the node hello version
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the node's last HELLO response
     *
     * @return array
     */
    public function getHello()
    {
        return $this->hello;
    }

    /**
     * Get the node job count since the last reset (usually a node switch)
     *
     * @return int
     */
    public function getJobCount()
    {
        return $this->jobCount;
    }

    /**
     * Increase the node job counts by the given number
     *
     * @param int $jobsAdded
     */
    public function addJobCount($jobsAdded)
    {
        $this->jobCount += $jobsAdded;
        $this->totalJobCount += $jobsAdded;

    }

    /**
     *  Reset the node job count
     */
    public function resetJobCount()
    {
        $this->jobCount = 0;
    }

    /**
     * Get the total job count since the node instantiation
     *
     * @return int
     */
    public function getTotalJobCount()
    {
        return $this->totalJobCount;
    }

    /**
     * Connect to the node and return the HELLO response
     *
     * This method is idempotent and can be called multiple times
     *
     * @return array The HELLO response
     *
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    public function connect()
    {
        if ($this->connection->isConnected() and !empty($this->hello)) {
            return $this->hello;
        }

        $this->connectToTheNode();
        $this->authenticateWithPassword();

        try {
            $this->sayHello();
        } catch (ResponseException $e) {
            /**
             * If the node requires a password but we didn't supply any,
             * Disque returns a message "NOAUTH Authentication required"
             *
             * HELLO is the first place we would get this error.
             *
             * @see https://github.com/antirez/disque/blob/master/src/server.c
             * Look for "noautherr"
             */
            $message = $e->getMessage();
            if (stripos($message, self::AUTH_REQUIRED_MESSAGE) === 0) {
                throw new AuthenticationException($message);
            }
        }

        return $this->hello;
    }

    /**
     * Say a new HELLO to the node and parse the response
     *
     * @return array The HELLO response
     *
     * @throws ConnectionException
     */
    public function sayHello()
    {
        $helloCommand = new Hello();
        $helloResponse = $this->connection->execute($helloCommand);
        $this->hello = $helloCommand->parse($helloResponse);

        $this->id = $this->hello[HelloResponse::NODE_ID];
        $this->createPrefix($this->id);

        $this->version = $this->hello[HelloResponse::NODE_VERSION];

        return $this->hello;
    }

    /**
     * Connect to the node
     *
     * @throws ConnectionException
     */
    private function connectToTheNode()
    {
        $this->connection->connect(
            $this->credentials->getConnectionTimeout(),
            $this->credentials->getResponseTimeout()
        );
    }

    /**
     * Authenticate with the node with a password, if set
     *
     * @throws AuthenticationException
     */
    private function authenticateWithPassword()
    {
        if ($this->credentials->havePassword()) {
            $authCommand = new Auth();
            $authCommand->setArguments([$this->credentials->getPassword()]);
            $authResponse = $this->connection->execute($authCommand);
            $response = $authCommand->parse($authResponse);
            if ($response !== self::AUTH_SUCCESS_MESSAGE) {
                throw new AuthenticationException();
            }
        }
    }

    /**
     * Create a node prefix from the node ID
     *
     * @param string $id
     */
    private function createPrefix($id)
    {
        $this->prefix = substr($id, self::PREFIX_START, self::PREFIX_LENGTH);
    }
}
