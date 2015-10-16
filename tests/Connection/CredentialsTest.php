<?php
namespace Disque\Test\Connection;

use Mockery as m;
use Disque\Connection\Credentials;

class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $c = new Credentials('127.0.0.1', 1111);
        $this->assertInstanceOf(Credentials::class, $c);
    }

    public function testGetHost()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $c = new Credentials($host, $port);
        $this->assertSame($host, $c->getHost());
    }

    public function testGetPort()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $c = new Credentials($host, $port);
        $this->assertSame($port, $c->getPort());
    }

    public function testGetAddress()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $address = '127.0.0.1:1234';
        $c = new Credentials($host, $port);
        $this->assertSame($address, $c->getAddress());
    }

    public function testDefaultValues()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $c = new Credentials($host, $port);
        $this->assertNull($c->getPassword());
        $this->assertFalse($c->havePassword());
        $this->assertNull($c->getConnectionTimeout());
        $this->assertNull($c->getResponseTimeout());
    }

    public function testGetPassword()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $password = 'password';
        $c = new Credentials($host, $port, $password);
        $this->assertSame($password, $c->getPassword());
    }

    public function testHavePassword()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $password1 = null;
        $c = new Credentials($host, $port, $password1);
        $this->assertFalse($c->havePassword());

        $password2 = 'password';
        $c2 = new Credentials($host, $port, $password2);
        $this->assertTrue($c2->havePassword());
    }

    public function testGetConnectionTimeout()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $password = null;
        $connectionTimeout = 1000;
        $c = new Credentials($host, $port, $password, $connectionTimeout);
        $this->assertSame($connectionTimeout, $c->getConnectionTimeout());
    }

    public function testGetResponseTimeout()
    {
        $host = '127.0.0.1';
        $port = 1234;
        $password = null;
        $connectionTimeout = 1000;
        $responseTimeout = 2000;
        $c = new Credentials($host, $port, $password, $connectionTimeout, $responseTimeout);
        $this->assertSame($responseTimeout, $c->getResponseTimeout());
    }
}
