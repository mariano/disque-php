<?php
namespace Disque\Test\Command;

use DateTime;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Client;
use Disque\Command;
use Disque\Command\CommandInterface;
use Disque\Connection\BaseConnection;
use Disque\Connection\Socket;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Exception\ConnectionException;
use Disque\Exception\InvalidCommandException;

class MockClient extends Client
{
    private $connection;

    private $availableConnection;

    private $buildConnection = null;

    public function getServers()
    {
        return $this->servers;
    }

    public function getCommandHandlers()
    {
        return $this->commandHandlers;
    }

    public function setCommand($commandName, CommandInterface $command)
    {
        $this->commandHandlers[$commandName] = get_class($command);
        $this->commands[$commandName] = $command;
    }

    public function getConnectionImplementation()
    {
        return $this->connectionImplementation;
    }

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
        if ($this->availableConnection === false) {
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

    public function execute(CommandInterface $command)
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

class ClientTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $c = new Client();
        $this->assertInstanceOf(Client::class, $c);
    }

    public function testConstruct()
    {
        $c = new MockClient();
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711]
        ], $c->getServers());
    }

    public function testConstructNoServers()
    {
        $c = new MockClient([]);
        $this->assertSame([], $c->getServers());
    }

    public function testConstructInvalidServers()
    {
        $c = new MockClient([':7711']);
        $this->assertSame([], $c->getServers());
    }

    public function testConstructMultipleServers()
    {
        $c = new MockClient([
            '127.0.0.1:7711',
            '127.0.0.1:7712',
        ]);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $c->getServers());
    }

    public function testConstructMultipleServersDefaultPort()
    {
        $c = new MockClient([
            '127.0.0.1',
            '127.0.0.1:7712',
        ]);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $c->getServers());
    }

    public function testAddServerInvalidHost()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Invalid server specified');
        $c = new Client();
        $c->addServer(128);
    }

    public function testAddServerInvalidPort()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Invalid server specified');
        $c = new Client();
        $c->addServer('127.0.0.1', false);
    }

    public function testAddServer()
    {
        $c = new MockClient();
        $c->addServer('127.0.0.1', 7712);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $c->getServers());
    }

    public function testAddServerDefaultPort()
    {
        $c = new MockClient();
        $c->addServer('other.host');
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => 'other.host', 'port' => 7711],
        ], $c->getServers());
    }

    public function testDefaultConnectionImplementation()
    {
        $c = new MockClient();
        $this->assertSame(Socket::class, $c->getConnectionImplementation());
    }

    public function testSetConnectionImplementationInvalidClass()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Class DateTime does not implement ConnectionInterface');
        $c = new Client();
        $c->setConnectionImplementation(DateTime::class);
    }

    public function testSetConnectionImplementation()
    {
        $connection = m::mock(ConnectionInterface::class);
        $c = new MockClient();
        $c->setConnectionImplementation(get_class($connection));
        $this->assertSame(get_class($connection), $c->getConnectionImplementation());
    }

    public function testCommandsRegistered()
    {
        $expectedCommands = [
            'ACKJOB' => Command\AckJob::class,
            'ADDJOB' => Command\AddJob::class,
            'DELJOB' => Command\DelJob::class,
            'DEQUEUE' => Command\Dequeue::class,
            'ENQUEUE' => Command\Enqueue::class,
            'FASTACK' => Command\FastAck::class,
            'GETJOB' => Command\GetJob::class,
            'HELLO' => Command\Hello::class,
            'INFO' => Command\Info::class,
            'QLEN' => Command\QLen::class,
            'QPEEK' => Command\QPeek::class,
            'SHOW' => Command\Show::class
        ];

        $c = new MockClient();
        $commands = $c->getCommandHandlers();
        foreach ($commands as $command => $class) {
            $this->assertTrue(array_key_exists($command, $expectedCommands));
            $this->assertSame($expectedCommands[$command], $class);
        }
    }

    public function testConnectInvalidNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $available = [
        ];
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
    }

    public function testConnectInvalidNoHello()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid HELLO response when connecting');
        $available = [
            'connection' => m::mock(ConnectionInterface::class)
        ];
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
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
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
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
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
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
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
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
        $c = new MockClient();
        $c->setAvailableConnection($available);
        $c->connect();
        $this->assertSame('NODE_ID', $c->getNodeId());
        $this->assertEquals([
            'NODE_ID' => $available['hello']['nodes'][0] + [
                'connection' => $available['connection']
            ]
        ], $c->getNodes());
    }

    public function testFindAvailableConnectionNoneAvailableConnectThrowsException()
    {
        $c = new MockClient();
        $c->setAvailableConnection(false); // Passthru

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->mock();

        $c->setBuildConnection($connection);

        $this->setExpectedException(ConnectionException::class, 'No servers available');

        $c->connect();
    }

    public function testFindAvailableConnectionSucceedsFirst()
    {
        $c = new MockClient();
        $c->setAvailableConnection(false); // Passthru
        $commandHandlers = $c->getCommandHandlers();

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type($commandHandlers['HELLO']))
            ->andReturn(['version', 'id', ['id', 'host', 'port', 'version']])
            ->once()
            ->mock();

        $c->setBuildConnection($connection);

        $result = $c->connect();
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
        $c = new MockClient();
        $c->addServer('127.0.0.1', 7712);
        $c->setAvailableConnection(false); // Passthru
        $commandHandlers = $c->getCommandHandlers();

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with([])
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->shouldReceive('connect')
            ->with([])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type($commandHandlers['HELLO']))
            ->andReturn(['version', 'id', ['id', 'host', 'port', 'version']])
            ->once()
            ->mock();

        $c->setBuildConnection($connection);

        $result = $c->connect();
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
        $client = new Client(['host:7799']);
        $client->setConnectionImplementation(MockConnection::class);

        try {
            $client->connect();
            $this->fail('An expected ' . ConnectionException::class . ' was not raised');
        } catch (ConnectionException $e) {
            $this->assertSame('No servers available', $e->getMessage());
        }
        $this->assertSame('host', MockConnection::$mockHost);
        $this->assertSame(7799, MockConnection::$mockPort);
    }

    public function testRegisterCommandInvalidClass()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Class DateTime does not implement CommandInterface');
        $c = new Client();
        $c->registerCommand('MYCOMMAND', DateTime::class);
    }

    public function testCallCommandInvalid()
    {
        $this->setExpectedException(InvalidCommandException::class, 'Invalid command WRONGCOMMAND');
        $c = new Client();
        $c->WrongCommand();
    }

    public function testCallCommandInvalidCaseInsensitive()
    {
        $this->setExpectedException(InvalidCommandException::class, 'Invalid command WRONGCOMMAND');
        $c = new Client();
        $c->wrongcommand();
    }

    public function testCallCommandInvalidNoServers()
    {
        $this->setExpectedException(ConnectionException::class, 'Not connected');
        $c = new Client([]);
        $c->hello();
    }

    public function testCallCommandCustom()
    {
        $command = m::mock(CommandInterface::class)
            ->shouldReceive('setArguments')
            ->with(['id'])
            ->once()
            ->shouldReceive('parse')
            ->with('RESPONSE')
            ->andReturn('PARSED_RESPONSE')
            ->once()
            ->mock();

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('execute')
            ->with($command)
            ->andReturn('RESPONSE')
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnection($connection);
        $c->setCommand('MYCOMMAND', $command);

        $result = $c->MyCommand('id');
        $this->assertSame('PARSED_RESPONSE', $result);
    }
}