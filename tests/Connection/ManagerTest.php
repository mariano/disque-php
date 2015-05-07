<?php
namespace Disque\Test\Connection;

use DateTime;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\Hello;
use Disque\Connection\BaseConnection;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Exception\ConnectionException;
use Disque\Connection\Manager;
use Disque\Connection\Socket;

class MockManager extends Manager
{
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    protected function getConnection()
    {
        return $this->connection;
    }

    public function setAvailableConnection($availableConnection)
    {
        $this->availableConnection = $availableConnection;
    }

    protected function findAvailableConnection(array $options)
    {
        if (!isset($this->availableConnection) || $this->availableConnection === false) {
            return parent::findAvailableConnection($options);
        }
        return $this->availableConnection;
    }

    public function setBuildConnection(ConnectionInterface $connection)
    {
        $this->buildConnection = $connection;
    }

    protected function buildConnection($host, $port)
    {
        if (isset($this->buildConnection)) {
            return $this->buildConnection;
        }
        return parent::buildConnection($host, $port);
    }

    public function getNodes()
    {
        return $this->nodes;
    }

    public function getNodeId()
    {
        return $this->nodeId;
    }
}

class MockConnection extends BaseConnection
{
    public static $mockHost;
    public static $mockPort;

    public function disconnect()
    {
    }

    public function isConnected()
    {
        return false;
    }

    public function execute(CommandInterface $mommand)
    {
    }

    public function setHost($host)
    {
        static::$mockHost = $host;
    }

    public function setPort($port)
    {
        static::$mockPort = $port;
    }
}

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testAddServerInvalidHost()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Invalid server specified');
        $m = new Manager();
        $m->addServer(128);
    }

    public function testAddServerInvalidPort()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Invalid server specified');
        $m = new Manager();
        $m->addServer('127.0.0.1', false);
    }

    public function testAddServer()
    {
        $m = new Manager();
        $m->addServer('127.0.0.1', 7712);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $m->getServers());
    }

    public function testAddServerDefaultPort()
    {
        $m = new Manager();
        $m->addServer('other.host');
        $this->assertEquals([
            ['host' => 'other.host', 'port' => 7711],
        ], $m->getServers());
    }

    public function testAddServers()
    {
        $m = new Manager();
        $m->addServer('127.0.0.1', 7711);
        $m->addServer('127.0.0.1', 7712);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $m->getServers());
    }

    public function testDefaultConnectionClass()
    {
        $m = new Manager();
        $this->assertSame(Socket::class, $m->getConnectionClass());
    }

    public function testSetConnectionClassInvalidClass()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Class DateTime does not implement ConnectionInterface');
        $m = new Manager();
        $m->setConnectionClass(DateTime::class);
    }

    public function testSetConnectionClass()
    {
        $connection = m::mock(ConnectionInterface::class);
        $m = new Manager();
        $m->setConnectionClass(get_class($connection));
        $this->assertSame(get_class($connection), $m->getConnectionClass());
    }

    public function testConnectInvalidNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $available = [
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
    }

    public function testConnectInvalidNoHello()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid HELLO response when connecting');
        $available = [
            'connection' => m::mock(ConnectionInterface::class)
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
    }

    public function testConnectInvalidHelloNoNodes()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid HELLO response when connecting');
        $available = [
            'connection' => m::mock(ConnectionInterface::class),
            'hello' => [
                'nodes' => []
            ]
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
    }

    public function testConnectInvalidHelloNoId()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid HELLO response when connecting');
        $available = [
            'connection' => m::mock(ConnectionInterface::class),
            'hello' => [
                'nodes' => [
                    [
                        'id' => 'NODE_ID',
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ]
                ]
            ]
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
    }

    public function testConnectInvalidNodeId()
    {
        $this->setExpectedException(ConnectionException::class, 'Connected node #NEW_NODE_ID could not be found in list of nodes');
        $available = [
            'connection' => m::mock(ConnectionInterface::class),
            'hello' => [
                'id' => 'NEW_NODE_ID',
                'nodes' => [
                    [
                        'id' => 'NODE_ID',
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ]
                ]
            ]
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
    }

    public function testConnect()
    {
        $available = [
            'connection' => m::mock(ConnectionInterface::class),
            'hello' => [
                'id' => 'NODE_ID',
                'nodes' => [
                    [
                        'id' => 'NODE_ID',
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ]
                ]
            ]
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
        $this->assertSame('NODE_ID', $m->getNodeId());
        $this->assertEquals([
            'NODE_ID' => $available['hello']['nodes'][0] + [
                'connection' => $available['connection']
            ]
        ], $m->getNodes());
    }

    public function testFindAvailableConnectionNoneSpecifiedConnectThrowsException()
    {
        $m = new MockManager();
        $m->setAvailableConnection(false); // Passthru
        $this->setExpectedException(ConnectionException::class, 'No servers specified');
        $m->connect([]);
    }

    public function testFindAvailableConnectionNoneAvailableConnectThrowsException()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711);
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $this->setExpectedException(ConnectionException::class, 'No servers available');

        $m->connect([]);
    }

    public function testFindAvailableConnectionSucceedsFirst()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711);
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn(['version', 'id', ['id', 'host', 'port', 'version']])
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $result = $m->connect([]);
        $this->assertSame([
            'version' => 'version',
            'id' => 'id',
            'nodes' => [
                [
                    'id' => 'id',
                    'host' => 'host',
                    'port' => 'port',
                    'version' => 'version'
                ]
            ]
        ], $result);
    }

    public function testFindAvailableConnectionSucceedsSecond()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711);
        $m->addServer('127.0.0.1', 7712);
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn(['version', 'id', ['id', 'host', 'port', 'version']])
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $result = $m->connect([]);
        $this->assertSame([
            'version' => 'version',
            'id' => 'id',
            'nodes' => [
                [
                    'id' => 'id',
                    'host' => 'host',
                    'port' => 'port',
                    'version' => 'version'
                ]
            ]
        ], $result);
    }

    public function testCustomConnection()
    {
        $m = new Manager();
        $m->addServer('host', 7799);
        $m->setConnectionClass(MockConnection::class);

        try {
            $m->connect([]);
            $this->fail('An expected ' . ConnectionException::class . ' was not raised');
        } catch (ConnectionException $e) {
            $this->assertSame('No servers available', $e->getMessage());
        }
        $this->assertSame('host', MockConnection::$mockHost);
        $this->assertSame(7799, MockConnection::$mockPort);
    }

    public function testExecuteNotConnected()
    {
        $m = new MockManager();
        $m->setAvailableConnection(false); // Passthru
        $this->setExpectedException(ConnectionException::class, 'Not connected');
        $m->execute(new Hello());
    }

    public function testExecuteCallsConnectionExecute()
    {
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn(['test' => 'stuff'])
            ->once()
            ->mock();

        $available = [
            'connection' => $connection,
            'hello' => [
                'id' => 'NODE_ID',
                'nodes' => [
                    [
                        'id' => 'NODE_ID',
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ]
                ]
            ]
        ];
        $m = new MockManager();
        $m->setAvailableConnection($available);
        $m->connect([]);
        $result = $m->execute(new Hello());
        $this->assertSame(['test' => 'stuff'], $result);
    }
}