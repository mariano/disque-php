<?php
namespace Disque\Test\Connection\Factory;

use Disque\Connection\Factory\PredisFactory;
use Disque\Connection\Predis;
use Mockery as m;

class PredisFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $f = new PredisFactory();
        $this->assertInstanceOf(PredisFactory::class, $f);
    }

    public function testCreate()
    {
        $host = '127.0.0.1';
        $port = 7711;

        $f = new PredisFactory();
        $socket = $f->create($host, $port);

        $this->assertInstanceOf(Predis::class, $socket);
    }
}
