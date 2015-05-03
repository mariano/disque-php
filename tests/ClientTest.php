<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Client;

class MockClient extends Client
{
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
        $c = new Client();
        $this->assertSame('127.0.0.1', $c->getHost());
        $this->assertSame(7711, $c->getPort());
    }
}