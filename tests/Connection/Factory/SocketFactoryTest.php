<?php
namespace Disque\Test\Connection\Factory;

use Disque\Connection\Factory\SocketFactory;
use Disque\Connection\Socket;
use Mockery as m;

class SocketFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $f = new SocketFactory();
        $this->assertInstanceOf(SocketFactory::class, $f);
    }

    public function testCreate()
    {
        $host = '127.0.0.1';
        $port = 7711;

        $f = new SocketFactory();
        $socket = $f->create($host, $port);

        $this->assertInstanceOf(Socket::class, $socket);
    }
}
