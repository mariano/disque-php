<?php
namespace Disque\Test\Connection;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Command;
use Disque\Connection\Exception\ConnectionException;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\Predis;

class MockPredis extends Predis
{
    public function setClient($client)
    {
        $this->client = $client;
    }

    protected function buildClient($host, $port)
    {
        return $this->client;
    }
}

class PredisTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $c = new Predis();
        $this->assertInstanceOf(ConnectionInterface::class, $c);
    }

    public function testIsConnected()
    {
        $c = new Predis();
        $result = $c->isConnected();
        $this->assertFalse($result);
    }

    public function testConnect()
    {
        $client = m::mock()
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->once()
            ->shouldReceive('connect')
            ->once()
            ->mock();
        $connection = new MockPredis();
        $connection->setClient($client);
        $connection->connect();
    }

    public function testDisconnect()
    {
        $client = m::mock()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->once()
            ->shouldReceive('disconnect')
            ->once()
            ->mock();
        $connection = new MockPredis();
        $connection->setClient($client);
        $connection->disconnect();
    }

    public function testExecuteErrorNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No connection established');

        $connection = new Predis();
        $connection->execute(new Command\Hello());
    }

    public function testExecuteHello()
    {
        $client = m::mock()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->once()
            ->shouldReceive('executeRaw')
            ->with(['HELLO'])
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->once()
            ->mock();
        $connection = new MockPredis();
        $connection->setClient($client);
        $connection->execute(new Command\Hello());
    }

    public function testExecuteAckJob()
    {
        $command = new Command\AckJob();
        $command->setArguments(['id']);

        $client = m::mock()
            ->shouldReceive('isConnected')
            ->andReturn(true)
            ->once()
            ->shouldReceive('executeRaw')
            ->with(['ACKJOB', 'id'])
            ->once()
            ->shouldReceive('isConnected')
            ->andReturn(false)
            ->once()
            ->mock();
        $connection = new MockPredis();
        $connection->setClient($client);
        $connection->execute($command);
    }
}