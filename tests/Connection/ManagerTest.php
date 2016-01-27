<?php
namespace Disque\Test\Connection;

use Closure;
use DateTime;
use Disque\Command\Response\HelloResponse;
use Disque\Connection\Factory\ConnectionFactoryInterface;
use Disque\Connection\Factory\SocketFactory;
use Disque\Connection\Node\ConservativeJobCountPrioritizer;
use Disque\Connection\Node\NodePrioritizerInterface;
use InvalidArgumentException;
use Metadata\Tests\Driver\Fixture\C\SubDir\C;
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
use Disque\Connection\Credentials;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testAddServer()
    {
        $m = new Manager();
        $s = new Credentials('127.0.0.1', 7712);
        $m->addServer($s);
        $this->assertEquals([$s], array_values($m->getCredentials()));
    }

    public function testAddServers()
    {
        $m = new Manager();
        $s1 = new Credentials('127.0.0.1', 7711, 'my_password1');
        $m->addServer($s1);
        $s2 = new Credentials('127.0.0.1', 7712);
        $m->addServer($s2);
        $s3 = new Credentials('127.0.0.1', 7713, 'my_password3');
        $m->addServer($s3);
        $this->assertEquals([$s1, $s2, $s3], array_values($m->getCredentials()));
    }

    public function testDefaultConnectionFactory()
    {
        $m = new Manager();
        $this->assertSame(SocketFactory::class, get_class($m->getConnectionFactory()));
    }

    public function testSetConnectionFactory()
    {
        $connectionFactory = m::mock(ConnectionFactoryInterface::class);
        $m = new Manager();
        $m->setConnectionFactory($connectionFactory);
        $this->assertSame($connectionFactory, $m->getConnectionFactory());
    }

    public function testSetPriorityStrategy()
    {
        $priorityStrategy = m::mock(NodePrioritizerInterface::class);
        $m = new Manager();
        $m->setPriorityStrategy($priorityStrategy);
        $this->assertSame($priorityStrategy, $m->getPriorityStrategy());
    }

    public function testConnectInvalidNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m = new Manager();
        $m->connect();
    }

    public function testConnect()
    {
        $m = new Manager();

        $serverAddress = '127.0.0.1';
        $serverPort = 7712;

        $nodeId = 'id1';
        $version = 'v1';
        $priority = 10;

        $server = new Credentials($serverAddress, $serverPort);
        $m->addServer($server);

        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $serverAddress,
                HelloResponse::POS_NODE_PORT => $serverPort,
                HelloResponse::POS_NODE_PRIORITY => $priority
            ]
        ];

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->times(2)
            ->andReturn(false, true)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('execute')
            ->andReturn($helloResponse)

            ->getMock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->with($serverAddress, $serverPort)
            ->andReturn($connection)
            ->once()
            ->getMock();

        $m->setConnectionFactory($connectionFactory);
        $node = $m->connect();

        $this->assertSame($connection, $node->getConnection());
        $this->assertSame($nodeId, $node->getId());
        $this->assertSame($priority, $node->getPriority());
        $this->assertSame($server, $node->getCredentials());
    }

    public function testConnectWithPasswordMissingPassword()
    {
        $m = new Manager();
        $m->addServer(new Credentials('127.0.0.1', 7711));

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('connect')
            ->with(null, null)
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andThrow(new AuthenticationException('NOAUTH Authentication Required'))
            ->once()
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->getMock();

        $m->setConnectionFactory($connectionFactory);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect();
    }

    public function testConnectWithPasswordWrongPassword()
    {
        $m = new Manager();
        $m->addServer(new Credentials('127.0.0.1', 7711, 'wrong_password'));

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->with(null, null)
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andThrow(new ResponseException('ERR invalid password'))
            ->once()
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->getMock();

        $m->setConnectionFactory($connectionFactory);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect();
    }

    public function testConnectWithPasswordWrongResponse()
    {
        $m = new Manager();
        $m->addServer(new Credentials('127.0.0.1', 7711, 'right_password'));

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->with(null, null)
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andReturn('WHATEVER')
            ->once()
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->getMock();

        $m->setConnectionFactory($connectionFactory);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect();
    }

    public function testConnectWithPasswordRightPassword()
    {
        $address = '127.0.0.1';
        $port = 7711;
        $port2 = 7712;

        $nodeId1 = 'id1';
        $nodeId2 = 'id2';
        $version = 'v1';

        $m = new Manager();
        $m->addServer(new Credentials($address, $port, 'right_password'));

        $helloResponse = [
            $version,
            $nodeId1,
            [$nodeId1, $address, $port, $version],
            [$nodeId2, $address, $port2, $version]
        ];

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->times(2)
            ->andReturn(false, true)
            ->shouldReceive('connect')
            ->with(null, null)
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andReturn('OK')
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn($helloResponse)
            ->mock();
        $connection2 = m::mock(ConnectionInterface::class);

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->times(2)
            ->andReturn($connection, $connection2)
            ->getMock();
        $m->setConnectionFactory($connectionFactory);

        $m->connect();
    }

    public function testFindAvailableConnectionNoneSpecifiedConnectThrowsException()
    {
        $m = new Manager();
        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect();
    }

    public function testFindAvailableConnectionNoneAvailableConnectThrowsException()
    {
        $m = new Manager();
        $m->addServer(new Credentials('127.0.0.1', 7711));

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->mock();
        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->getMock();
        $m->setConnectionFactory($connectionFactory);
        $this->setExpectedException(ConnectionException::class, 'No servers available');

        $m->connect();
    }

    public function testFindAvailableConnectionSucceedsFirst()
    {
        $serverAddress = '127.0.0.1';
        $serverPort = 7712;

        $server = new Credentials($serverAddress, $serverPort);
        $m = new Manager();
        $m->addServer($server);

        $nodeId = 'id1';
        $version = 'v1';
        $priority = 2;

        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $serverAddress,
                HelloResponse::POS_NODE_PORT => $serverPort,
                HelloResponse::POS_NODE_PRIORITY => $priority
            ]
        ];

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->times(2)
            ->andReturn(false, true)
            ->shouldReceive('connect')
            ->with(null, null)
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn($helloResponse)
            ->once()
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->getMock();
        $m->setConnectionFactory($connectionFactory);

        $node = $m->connect();

        $this->assertSame($connection, $node->getConnection());
        $this->assertSame($nodeId, $node->getId());
        $this->assertSame($priority, $node->getPriority());
        $this->assertSame($server, $node->getCredentials());

    }

    public function testFindAvailableConnectionSucceedsSecond()
    {
        $serverAddress = '127.0.0.1';
        $serverPort1 = 7711;
        $serverPort2 = 7712;

        $server1 = new Credentials($serverAddress, $serverPort1);
        $server2 = new Credentials($serverAddress, $serverPort2);
        $m = new Manager();
        $m->addServer($server1);
        $m->addServer($server2);

        $nodeId = 'id1';
        $version = 'v1';
        $priority = 5;

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->times(3)
            ->andReturn(false, false, true)
            ->shouldReceive('connect')
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->once()
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([$version, $nodeId, [$nodeId, $serverAddress, $serverPort2, $priority]])
            ->once()
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->times(2)
            ->andReturn($connection)
            ->getMock();
        $m->setConnectionFactory($connectionFactory);

        $node = $m->connect();

        $this->assertSame($connection, $node->getConnection());
        $this->assertSame($nodeId, $node->getId());
        $this->assertSame($priority, $node->getPriority());
        $this->assertContains($node->getCredentials(), [$server1, $server2]);
    }

    public function testCustomConnection()
    {
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andThrow(ConnectionException::class)
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->andReturn($connection)
            ->getMock();

        $m = new Manager();
        $m->addServer(new Credentials('host', 7799));
        $m->setConnectionFactory($connectionFactory);

        $this->setExpectedException(ConnectionException::class, 'No servers available');
        $m->connect();
    }

    public function testExecuteNotConnected()
    {
        $m = new Manager();
        $this->setExpectedException(ConnectionException::class, 'Not connected');
        $m->execute(new Hello());
    }

    public function testExecuteCallsConnectionExecute()
    {
        $m = new Manager();

        $serverAddress = '127.0.0.1';
        $serverPort = 7712;
        $nodeId = 'id1';
        $version = 'v1';
        $server = new Credentials($serverAddress, $serverPort);
        $m->addServer($server);

        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $serverAddress,
                HelloResponse::POS_NODE_PORT => $serverPort,
                HelloResponse::POS_NODE_PRIORITY => $version
            ]
        ];
        $expectedResponse = ['test' => 'stuff'];

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->times(3)
            ->andReturn(false, true, true)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('execute')
            ->andReturn($helloResponse)
            ->once()
            ->shouldReceive('execute')
            ->andReturn($expectedResponse)
            ->getMock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->with($serverAddress, $serverPort)
            ->andReturn($connection)
            ->once()

            ->getMock();

        $m->setConnectionFactory($connectionFactory);
        $m->connect();
        $result = $m->execute(new Hello());
        $this->assertSame($expectedResponse, $result);
    }

    public function testGetJobSwitchesNode()
    {
        $node1 = '0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = '0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $jobId1 = 'D-' . $node1;
        $jobId2 = 'D-' . $node2;
        $jobId3 = 'D-' . $node2;

        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                $node1,
                [$node1, '127.0.0.1', 7711, 'v1'],
                [$node2, '127.0.0.1', 7712, 'v1']
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId1, 'body1']
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $jobId2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId3, 'body3']
            ])
            ->mock();

        $connection2 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                $node2,
                [$node2, '127.0.0.1', 7712, 'v1'],
                [$node1, '127.0.0.1', 7711, 'v1']
            ])
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection1)
            ->shouldReceive('create')
            ->andReturn($connection2)
            ->once()
            ->mock();

        $priorityStrategy = new ConservativeJobCountPrioritizer();
        $priorityStrategy->setMarginToSwitch(0.001);

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new Manager();
        $m->setConnectionFactory($connectionFactory);
        $m->setPriorityStrategy($priorityStrategy);
        $m->addServer(new Credentials('127.0.0.1', 7711));


        $m->connect();

        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node2, $m->getCurrentNode()->getId());
    }

    public function testGetJobNodeNotInList()
    {
        $node1 = '0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = '0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $jobId1 = 'D-' . $node1;
        $jobId2 = 'D-' . $node2;
        $jobId3 = 'D-' . $node2;

        $this->assertNotSame($node1, $node2);

        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                $node1,
                [$node1, '127.0.0.1', 7711, 'v1']
            ])
            ->once()
            ->shouldReceive('execute')

            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId1, 'body1']
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $jobId2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId3, 'body3']
            ])
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection)
            ->mock();

        $priorityStrategy = new ConservativeJobCountPrioritizer();
        $priorityStrategy->setMarginToSwitch(0.001);

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new Manager();
        $m->setConnectionFactory($connectionFactory);
        $m->setPriorityStrategy($priorityStrategy);
        $m->addServer(new Credentials('127.0.0.1', 7711));


        $m->connect();

        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());
    }

    public function testGetJobSameNode()
    {
        $node1 = '0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = '0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $jobId1 = 'D-' . $node1;
        $jobId2 = 'D-' . $node2;
        $jobId3 = 'D-' . $node1;

        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                $node1,
                [$node1, '127.0.0.1', 7711, 'v1'],
                [$node2, '127.0.0.1', 7712, 'v1']
            ])
            ->once()
            ->shouldReceive('execute')

            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId1, 'body1']
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $jobId2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId3, 'body3']
            ])
            ->mock();

        $connection2 = m::mock(ConnectionInterface::class);
        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection1)
            ->shouldReceive('create')
            ->andReturn($connection2)
            ->once()
            ->mock();

        $priorityStrategy = new ConservativeJobCountPrioritizer();
        $priorityStrategy->setMarginToSwitch(0.001);

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new Manager();
        $m->setConnectionFactory($connectionFactory);
        $m->setPriorityStrategy($priorityStrategy);
        $m->addServer(new Credentials('127.0.0.1', 7711));


        $m->connect();

        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        return;
    }

    public function testGetJobChangeNodeCantConnect()
    {
        $node1 = '0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $node2 = '0f0c645fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $jobId1 = 'D-' . $node1;
        $jobId2 = 'D-' . $node2;
        $jobId3 = 'D-' . $node2;

        $this->assertNotSame($node1, $node2);

        $connection1 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->once()
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andReturn([
                'v1',
                $node1,
                [$node1, '127.0.0.1', 7711, 'v1'],
                [$node2, '127.0.0.1', 7712, 'v1']
            ])
            ->once()
            ->shouldReceive('execute')

            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId1, 'body1']
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q2', $jobId2, 'body2'],
            ])
            ->once()
            ->shouldReceive('execute')
            ->with(m::type(GetJob::class))
            ->andReturn([
                ['q', $jobId3, 'body3']
            ])
            ->mock();

        $connection2 = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->once()
            ->andThrow(new ConnectionException('Mocking ConnectionException'))
            ->mock();

        $connectionFactory = m::mock(ConnectionFactoryInterface::class)
            ->shouldReceive('create')
            ->once()
            ->andReturn($connection1)
            ->shouldReceive('create')
            ->andReturn($connection2)
            ->once()
            ->mock();

        $priorityStrategy = new ConservativeJobCountPrioritizer();
        $priorityStrategy->setMarginToSwitch(0.001);

        $command = new GetJob();
        $command->setArguments(['q', 'q2']);

        $m = new Manager();
        $m->setConnectionFactory($connectionFactory);
        $m->setPriorityStrategy($priorityStrategy);
        $m->addServer(new Credentials('127.0.0.1', 7711));


        $m->connect();

        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());

        $m->execute($command);
        $this->assertSame($node1, $m->getCurrentNode()->getId());
    }


}
