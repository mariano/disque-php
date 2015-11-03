<?php
namespace Disque\Connection\Node;

use Disque\Command\Auth;
use Disque\Command\Hello;
use Disque\Command\Response\HelloResponse;
use Disque\Connection\ConnectionException;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Credentials;
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
     * Disque-assigned node priorities
     * @see $priority
     */
    const PRIORITY_OK = 1;
    const PRIORITY_POSSIBLE_FAILURE = 10;
    const PRIORITY_FAILURE = 100;

    /**
     * A fallback node priority if the HELLO response doesn't contain a priority
     * This should not happen, but let's be sure.
     */
    const PRIORITY_FALLBACK = 2;

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
     * @var int Node priority set by Disque, 1-100, lower is better
     *
     * This priority is set by Disque, lower number is better. As of 09/2015
     * there are three possible values:
     *
     * 1 - Node is working correctly
     * 10 - Possible failure (PFAIL) - Node may be failing
     * 100 - Failure (FAIL) - The majority of nodes agree that the node is failing
     *
     * For priority values,
     * @see https://github.com/antirez/disque/blob/master/src/cluster.c, helloCommand()
     *
     * For the difference between PFAIL and FAIL states,
     * @see http://redis.io/topics/cluster-spec#failure-detection
     * @see also https://github.com/antirez/disque/blob/master/src/cluster.c
     * Look for CLUSTER_NODE_PFAIL and CLUSTER_NODE_FAIL
     *
     */
    private $priority = 1;

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
     * Get the node priority as set by the cluster. 1-100, lower is better.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority Disque priority as revealed by a HELLO
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
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
     * @throws ConnectionException
     * @throws AuthenticationException
     */
    public function connect()
    {
        if ($this->connection->isConnected() && !empty($this->hello)) {
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
        $this->hello = (array) $helloCommand->parse($helloResponse);

        $this->id = $this->hello[HelloResponse::NODE_ID];
        $this->createPrefix($this->id);

        $this->priority = $this->readPriorityFromHello($this->hello, $this->id);

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

    /**
     * Read out the node's own priority from a HELLO response
     *
     * @param array  $hello The HELLO response
     * @param string $id    Node ID
     *
     * @return int Node priority
     */
    private function readPriorityFromHello($hello, $id)
    {
        foreach ($hello[HelloResponse::NODES] as $node) {
            if ($node[HelloResponse::NODE_ID] === $id) {
                return $node[HelloResponse::NODE_PRIORITY];
            }
        }

        // Node not found in the HELLO? This should not happen.
        // Return a fallback value
        return self::PRIORITY_FALLBACK;
    }
}