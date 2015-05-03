<?php
namespace Disque\Test\Command;

use DateTime;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Client;
use Disque\Connection\Connection;
use Disque\Connection\ConnectionInterface;

class MockClient extends Client
{
    public function getServers()
    {
        return $this->servers;
    }

    public function getConnectionImplementation()
    {
        return $this->connectionImplementation;
    }

    public function mockGetConnection()
    {
        $this->connection = null;
        return $this->getConnection();
    }
}

class ClientTest extends PHPUnit_Framework_TestCase
{
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

    public function testConstructMultipleServers()
    {
        $c = new MockClient([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
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
        $c->addServer(['host' => 128]);
    }

    public function testAddServerInvalidPort()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Invalid server specified');
        $c = new Client();
        $c->addServer(['port' => false]);
    }

    public function testAddServer()
    {
        $c = new MockClient();
        $c->addServer(['host' => '127.0.0.1', 'port' => 7712]);
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $c->getServers());
    }

    public function testAddServerDefaultHost()
    {
        $c = new MockClient();
        $c->addServer(['port' => 7712]);
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => '127.0.0.1', 'port' => 7712],
        ], $c->getServers());
    }

    public function testAddServerDefaultPort()
    {
        $c = new MockClient();
        $c->addServer(['host' => 'other.host']);
        $this->assertEquals([
            ['host' => '127.0.0.1', 'port' => 7711],
            ['host' => 'other.host', 'port' => 7711],
        ], $c->getServers());
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

}