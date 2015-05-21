<?php
namespace Disque\Test\Connection;

use Closure;
use DateTime;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Command\Auth;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Command\Hello;
use Disque\Connection\AuthenticationException;
use Disque\Connection\BaseConnection;
use Disque\Connection\ConnectionException;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Manager;
use Disque\Connection\Response\ResponseException;
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

    protected function findAvailableConnection()
    {
        if (!isset($this->availableConnection) || $this->availableConnection === false) {
            return parent::findAvailableConnection();
        }
        return $this->availableConnection;
    }

    public function setBuildConnection($connection)
    {
        $this->buildConnection = $connection;
    }

    protected function buildConnection($host, $port)
    {
        if (isset($this->buildConnection)) {
            if ($this->buildConnection instanceof Closure) {
                return call_user_func($this->buildConnection, $host, $port);
            }
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
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7712, 'password' => null, 'options' => []],
        ], $m->getServers());
    }

    public function testAddServerDefaultPort()
    {
        $m = new Manager();
        $m->addServer('other.host');
        $this->assertEquals([
            ['host' => 'other.host', 'port' => 7711, 'password' => null, 'options' => []],
        ], $m->getServers());
    }

    public function testAddServerPassword()
    {
        $m = new Manager();
        $m->addServer('127.0.0.1', 7712, 'my_password');
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7712, 'password' => 'my_password', 'options' => []],
        ], $m->getServers());
    }

    public function testAddServers()
    {
        $m = new Manager();
        $m->addServer('127.0.0.1', 7711, 'my_password1');
        $m->addServer('127.0.0.1', 7712);
        $m->addServer('127.0.0.1', 7713, 'my_password3');
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7711, 'password' => 'my_password1', 'options' => []],
            ['host' => '127.0.0.1', 'port' => 7712, 'password' => null, 'options' => []],
            ['host' => '127.0.0.1', 'port' => 7713, 'password' => 'my_password3', 'options' => []],
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
                'connection' => $available['connection'],
                'jobs' => 0
            ]
        ], $m->getNodes());
    }

    public function testConnectWithOptions()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711, null, ['test' => 'stuff']);
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with(['test' => 'stuff'])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                'id1',
                ['id1', '127.0.0.1', 7711, 'v1'],
                ['id2', '127.0.0.1', 7712, 'v1']
            ])
            ->mock();

        $m->setBuildConnection($connection);
        $m->connect([]);
    }

    public function testConnectWithPasswordMissingPassword()
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
            ->andThrow(new ResponseException('NOAUTH Authentication Required'))
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $this->setExpectedException(AuthenticationException::class, 'NOAUTH Authentication Required');
        $m->connect([]);
    }

    public function testConnectWithPasswordWrongPassword()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711, 'wrong_password');
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andThrow(new ResponseException('ERR invalid password'))
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect([]);
    }

    public function testConnectWithPasswordWrongResponse()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711, 'right_password');
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andReturn('WHATEVER')
            ->once()
            ->mock();

        $m->setBuildConnection($connection);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect([]);
    }

    public function testConnectWithPasswordRightPassword()
    {
        $m = new MockManager();
        $m->addServer('127.0.0.1', 7711, 'right_password');
        $m->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andReturn('OK')
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                'id1',
                ['id1', '127.0.0.1', 7711, 'v1'],
                ['id2', '127.0.0.1', 7712, 'v1']
            ])
            ->mock();

        $m->setBuildConnection($connection);
        $m->connect([]);
    }

    public function testFindAvailableConnectionNoneSpecifiedConnectThrowsException()
    {
        $m = new MockManager();
        $m->setAvailableConnection(false); // Passthru
        $this->setExpectedException(ConnectionException::class, 'No servers available');
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

    public function testExecuteCallsConnectionDisconnected()
    {
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
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

        $this->setExpectedException(ConnectionException::class, 'Not connected');

        $m->execute(new Hello());
    }

    public function testExecuteCallsConnectionExecute()
    {
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->once()
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

    public function testGetJobNodeNotInList()
    {
        $node1 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = 'DI0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->times(3)
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node1, 'body1'],
                ['q', $node1, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $node2, 'body3'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node1, 'body4']
            ])
            ->mock();

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new MockManager();
        $m->setMinimumJobsToChangeNode(3);
        $m->setAvailableConnection([
            'connection' => $connection1,
            'hello' => [
                'id' => $node1,
                'nodes' => [
                    [
                        'id' => $node1,
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ]
                ]
            ]
        ]);
        $m->connect([]);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());
    }

    public function testGetJobSameNode()
    {
        $node1 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = 'DI0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->times(3)
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node1, 'body1'],
                ['q', $node1, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $node2, 'body3'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node1, 'body4']
            ])
            ->mock();

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new MockManager();
        $m->setMinimumJobsToChangeNode(3);
        $m->setAvailableConnection([
            'connection' => $connection1,
            'hello' => [
                'id' => $node1,
                'nodes' => [
                    [
                        'id' => $node1,
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ],
                    [
                        'id' => $node2,
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ],

                ]
            ]
        ]);
        $m->connect([]);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());
    }

    public function testGetJobChangeNodeCantConnect()
    {
        $node1 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = 'DI0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->times(3)
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node2, 'body1'],
                ['q', $node2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $node1, 'body3'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node2, 'body4']
            ])
            ->mock();

        $connection2 = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->mock();

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new MockManager();
        $m->setMinimumJobsToChangeNode(3);
        $m->setAvailableConnection([
            'connection' => $connection1,
            'hello' => [
                'id' => $node1,
                'nodes' => [
                    [
                        'id' => $node1,
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ],
                    [
                        'id' => $node2,
                        'host' => '127.0.0.1',
                        'port' => 7712
                    ],

                ]
            ]
        ]);
        $m->setBuildConnection(function ($host, $port) use($connection1, $connection2) {
            if ($port === 7711) {
                return $connection1;
            }
            return $connection2;
        });
        $m->connect([]);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());
    }

    public function testGetJobChangesNode()
    {
        $node1 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = 'DI0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->times(3)
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node2, 'body1'],
                ['q', $node2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $node1, 'body3'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $node2, 'body4']
            ])
            ->mock();

        $connection2 = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                'id1',
                ['id1', '127.0.0.1', 7711, 'v1'],
                ['id2', '127.0.0.1', 7712, 'v1']
            ])
            ->mock();

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new MockManager();
        $m->setMinimumJobsToChangeNode(3);
        $m->setAvailableConnection([
            'connection' => $connection1,
            'hello' => [
                'id' => $node1,
                'nodes' => [
                    [
                        'id' => $node1,
                        'host' => '127.0.0.1',
                        'port' => 7711
                    ],
                    [
                        'id' => $node2,
                        'host' => '127.0.0.1',
                        'port' => 7712
                    ],

                ]
            ]
        ]);
        $m->setBuildConnection(function ($host, $port) use($connection1, $connection2) {
            if ($port === 7711) {
                return $connection1;
            }
            return $connection2;
        });
        $m->connect([]);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node1, $m->getNodeId());

        $m->execute($command);
        $this->assertSame($node2, $m->getNodeId());
    }
}