<?php
namespace Disque\Test\Connection\Node;

use Disque\Command\Auth;
use Disque\Command\Hello;
use Disque\Command\Response\HelloResponse;
use Disque\Command\Response\InvalidResponseException;
use Disque\Connection\AuthenticationException;
use Disque\Connection\ConnectionException;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Credentials;
use Disque\Connection\Node\Node;
use Disque\Connection\Response\ResponseException;
use Mockery as m;

class NodeTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);
        $this->assertInstanceOf(Node::class, $n);
    }

    public function testGetConnection()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);
        $this->assertSame($connection, $n->getConnection());
    }

    public function testGetCredentials()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);
        $this->assertSame($credentials, $n->getCredentials());
    }

    public function testDefaultValues()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);

        $this->assertSame(0, $n->getJobCount());
        $this->assertSame(0, $n->getTotalJobCount());
        $this->assertNull($n->getId());
        $this->assertNull($n->getPrefix());
        $this->assertNull($n->getVersion());
        $this->assertNull($n->getHello());
    }

    public function testJobCount()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);

        $jobs1 = 2;
        $jobs2 = 3;
        $totalJobs = $jobs1 + $jobs2;

        $n->addJobCount($jobs1);
        $n->addJobCount($jobs2);

        $this->assertSame($totalJobs, $n->getJobCount());
        $this->assertSame($totalJobs, $n->getTotalJobCount());
    }

    public function testResetJobCount()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class);
        $n = new Node($credentials, $connection);

        $totalJobs = 5;

        $n->addJobCount($totalJobs);
        $n->resetJobCount();

        $this->assertSame(0, $n->getJobCount());
        $this->assertSame($totalJobs, $n->getTotalJobCount());
    }

    public function testSayHello()
    {
        $address = '127.0.0.1';
        $port = 7712;
        $nodeId = 'someLongNodeId';
        $prefix = 'someLong';
        $version = 'v1';
        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $address,
                HelloResponse::POS_NODE_PORT => $port,
                HelloResponse::POS_NODE_VERSION => $version
            ]
        ];
        $expectedHello = [
            HelloResponse::NODE_VERSION => $version,
            HelloResponse::NODE_ID => $nodeId,
            HelloResponse::NODES => [
                [
                    HelloResponse::NODE_ID => $nodeId,
                    HelloResponse::NODE_HOST => $address,
                    HelloResponse::NODE_PORT => $port,
                    HelloResponse::NODE_VERSION => $version
                ]
            ]
        ];

        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('execute')
            ->andReturn($helloResponse)
            ->getMock();
        $n = new Node($credentials, $connection);
        $hello = $n->sayHello();

        $this->assertSame($expectedHello, $hello);
        $this->assertNotNull($n->getHello());
        $this->assertSame($nodeId, $n->getId());
        $this->assertSame($version, $n->getVersion());
        $this->assertSame($prefix, $n->getPrefix());

    }

    public function testSayHelloConnectionException()
    {
        $credentials = m::mock(Credentials::class);
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('execute')
            ->andThrow(new ConnectionException())
            ->getMock();
        $n = new Node($credentials, $connection);

        $this->setExpectedException(ConnectionException::class);
        $n->sayHello();
    }

    public function testConnect()
    {
        $connectionTimeout = 1000;
        $responseTimeout = 1000;
        $address = '127.0.0.1';
        $port = 7712;
        $nodeId = 'someLongNodeId';
        $version = 'v1';
        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $address,
                HelloResponse::POS_NODE_PORT => $port,
                HelloResponse::POS_NODE_VERSION => $version
            ]
        ];
        $expectedHello = [
            HelloResponse::NODE_VERSION => $version,
            HelloResponse::NODE_ID => $nodeId,
            HelloResponse::NODES => [
                [
                    HelloResponse::NODE_ID => $nodeId,
                    HelloResponse::NODE_HOST => $address,
                    HelloResponse::NODE_PORT => $port,
                    HelloResponse::NODE_VERSION => $version
                ]
            ]
        ];
        $credentials = m::mock(Credentials::class)
            ->shouldReceive('getConnectionTimeout')
            ->andReturn($connectionTimeout)
            ->shouldReceive('getResponseTimeout')
            ->andReturn($responseTimeout)
            ->shouldReceive('havePassword')
            ->andReturn(false)
            ->getMock();
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->shouldReceive('execute')
            ->andReturn($helloResponse)
            ->getMock();

        $n = new Node($credentials, $connection);
        $hello = $n->connect();
        $this->assertSame($expectedHello, $hello);
    }

    public function testConnectConnectionException()
    {
        $connectionTimeout = 1000;
        $responseTimeout = 1000;
        $credentials = m::mock(Credentials::class)
            ->shouldReceive('getConnectionTimeout')
            ->andReturn($connectionTimeout)
            ->shouldReceive('getResponseTimeout')
            ->andReturn($responseTimeout)
            ->getMock();
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->andThrow(new ConnectionException())
            ->getMock();
        $n = new Node($credentials, $connection);
        $this->setExpectedException(ConnectionException::class);
        $n->connect();
    }


    public function testConnectWithPasswordRightPassword()
    {
        $connectionTimeout = 1000;
        $responseTimeout = 1000;
        $address = '127.0.0.1';
        $port = 7712;
        $nodeId = 'someLongNodeId';
        $version = 'v1';
        $password = 'password';
        $helloResponse = [
            HelloResponse::POS_VERSION => $version,
            HelloResponse::POS_ID => $nodeId,
            HelloResponse::POS_NODES_START => [
                HelloResponse::POS_NODE_ID => $nodeId,
                HelloResponse::POS_NODE_HOST => $address,
                HelloResponse::POS_NODE_PORT => $port,
                HelloResponse::POS_NODE_VERSION => $version
            ]
        ];
        $expectedHello = [
            HelloResponse::NODE_VERSION => $version,
            HelloResponse::NODE_ID => $nodeId,
            HelloResponse::NODES => [
                [
                    HelloResponse::NODE_ID => $nodeId,
                    HelloResponse::NODE_HOST => $address,
                    HelloResponse::NODE_PORT => $port,
                    HelloResponse::NODE_VERSION => $version
                ]
            ]
        ];
        $credentials = m::mock(Credentials::class)
            ->shouldReceive('getConnectionTimeout')
            ->andReturn($connectionTimeout)
            ->shouldReceive('getResponseTimeout')
            ->andReturn($responseTimeout)
            ->shouldReceive('havePassword')
            ->andReturn(true)
            ->shouldReceive('getPassword')
            ->andReturn($password)
            ->getMock();
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->shouldReceive('execute')
            ->andReturn('OK')
            ->once()
            ->shouldReceive('execute')
            ->andReturn($helloResponse)
            ->once()
            ->getMock();

        $n = new Node($credentials, $connection);
        $hello = $n->connect();
        $this->assertSame($expectedHello, $hello);
    }

    public function testConnectWithPasswordMissingPassword()
    {
        $connectionTimeout = 1000;
        $responseTimeout = 1000;

        $credentials = m::mock(Credentials::class)
            ->shouldReceive('getConnectionTimeout')
            ->andReturn($connectionTimeout)
            ->shouldReceive('getResponseTimeout')
            ->andReturn($responseTimeout)
            ->shouldReceive('havePassword')
            ->andReturn(false)
            ->getMock();
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->shouldReceive('execute')
            ->with(m::type(Hello::class))
            ->andThrow(new ResponseException('NOAUTH Authentication Required'))
            ->once()
            ->getMock();

        $n = new Node($credentials, $connection);
        $this->setExpectedException(AuthenticationException::class);
        $n->connect();
    }

    public function testConnectWithPasswordWrongPassword()
    {
        $connectionTimeout = 1000;
        $responseTimeout = 1000;
        $wrongPassword = 'wrongPassword';

        $credentials = m::mock(Credentials::class)
            ->shouldReceive('getConnectionTimeout')
            ->andReturn($connectionTimeout)
            ->shouldReceive('getResponseTimeout')
            ->andReturn($responseTimeout)
            ->shouldReceive('havePassword')
            ->andReturn(true)
            ->shouldReceive('getPassword')
            ->andReturn($wrongPassword)
            ->getMock();
        $connection = m::mock(ConnectionInterface::class)
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->shouldReceive('connect')
            ->shouldReceive('execute')
            ->with(m::type(Auth::class))
            ->andReturn('whatever')
            ->once()
            ->getMock();

        $n = new Node($credentials, $connection);
        $this->setExpectedException(AuthenticationException::class);
        $n->connect();
    }
}
