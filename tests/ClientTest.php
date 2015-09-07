<?php
namespace Disque\Test;

use DateTime;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Client;
use Disque\Command;
use Disque\Command\CommandInterface;
use Disque\Command\InvalidCommandException;
use Disque\Connection\ManagerInterface;
use Disque\Connection\ConnectionException;
use Disque\Connection\Credentials;
use Disque\Queue\Queue;

class MockClient extends Client
{
    public function getCommandHandlers()
    {
        return $this->commandHandlers;
    }

    public function setConnectionManager(ManagerInterface $manager)
    {
        $this->connectionManager = $manager;
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
        $this->assertSame([], $c->getConnectionManager()->getCredentials());
    }

    public function testConstructNoServers()
    {
        $c = new MockClient([]);
        $this->assertSame([], $c->getConnectionManager()->getCredentials());
    }

    public function testConstructMultipleServers()
    {
        $nodes = [
            new Credentials('127.0.0.1', '7711'),
            new Credentials('127.0.0.1', '7712'),
        ];

        $c = new Client($nodes);
        $this->assertEquals(
            $nodes,
            array_values($c->getConnectionManager()->getCredentials()));
    }

    public function testCommandsRegistered()
    {
        $expectedCommands = [
            new Command\AckJob(),
            new Command\AddJob(),
            new Command\DelJob(),
            new Command\Dequeue(),
            new Command\Enqueue(),
            new Command\FastAck(),
            new Command\GetJob(),
            new Command\Hello(),
            new Command\Info(),
            new Command\Nack(),
            new Command\QLen(),
            new Command\QPeek(),
            new Command\QScan(),
            new Command\Show(),
            new Command\Working()
        ];

        $c = new MockClient();
        $commands = $c->getCommandHandlers();
        $this->assertCount(count($commands), $expectedCommands);
        foreach ($commands as $command) {
            $strictComparison = false;
            $commandFound = in_array($command, $expectedCommands, $strictComparison);
            $this->assertTrue($commandFound);
        }
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

    public function testConnectCallsManagerConnect()
    {
        $manager = m::mock(ManagerInterface::class)
            ->shouldReceive('setOptions')
            ->with([])
            ->shouldReceive('connect')
            ->with()
            ->andReturn(['test' => 'stuff'])
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnectionManager($manager);

        $result = $c->connect();
        $this->assertSame(['test' => 'stuff'], $result);
    }

    public function testConnectWithOptionsCallsManager()
    {
        $manager = m::mock(ManagerInterface::class)
            ->shouldReceive('setOptions')
            ->with(['test' => 'stuff'])
            ->shouldReceive('connect')
            ->with()
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnectionManager($manager);

        $c->connect(['test' => 'stuff']);
    }

    public function testIsConnectedCallsManager()
    {
        $manager = m::mock(ManagerInterface::class)
            ->shouldReceive('isConnected')
            ->with()
            ->andReturn(true)
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnectionManager($manager);

        $result = $c->isConnected();
        $this->assertTrue($result);
    }

    public function testCallCommandCustom()
    {
        $commandName = 'MYCOMMAND';
        $commandArgument = 'id';
        $commandResponse = 'RESPONSE';
        $parsedResponse = 'PARSED RESPONSE';

        $command = m::mock(CommandInterface::class)
            ->shouldReceive('getCommand')
            ->andReturn($commandName)
            ->zeroOrMoreTimes()
            ->shouldReceive('setArguments')
            ->with([$commandArgument])
            ->once()
            ->shouldReceive('parse')
            ->with($commandResponse)
            ->andReturn($parsedResponse)
            ->once()
            ->mock();

        $manager = m::mock(ManagerInterface::class)
            ->shouldReceive('execute')
            ->with($command)
            ->andReturn($commandResponse)
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnectionManager($manager);
        $c->registerCommand($command);

        $result = $c->$commandName($commandArgument);
        $this->assertSame($parsedResponse, $result);
    }

    public function testQueue()
    {
        $c = new Client();
        $queue = $c->queue('queue');
        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testQueueDifferent()
    {
        $c = new Client();
        $queue = $c->queue('queue');
        $this->assertInstanceOf(Queue::class, $queue);
        $queue2 = $c->queue('queue2');
        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertNotSame($queue, $queue2);
    }

    public function testQueueSame()
    {
        $c = new Client();
        $queue = $c->queue('queue');
        $this->assertInstanceOf(Queue::class, $queue);
        $queue2 = $c->queue('queue');
        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertSame($queue, $queue2);
    }
}
