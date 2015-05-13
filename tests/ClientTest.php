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
use Disque\Queue\Queue;

class MockClient extends Client
{
    public function getCommandHandlers()
    {
        return $this->commandHandlers;
    }

    public function setCommand($commandName, CommandInterface $command)
    {
        $this->commandHandlers[$commandName] = get_class($command);
        $this->commands[$commandName] = $command;
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
        $this->assertSame([
            ['host' => '127.0.0.1', 'port' => 7711]
        ], $c->getConnectionManager()->getServers());
    }

    public function testConstructNoServers()
    {
        $c = new MockClient([]);
        $this->assertSame([], $c->getConnectionManager()->getServers());
    }

    public function testConstructInvalidServers()
    {
        $c = new MockClient([':7711']);
        $this->assertSame([], $c->getConnectionManager()->getServers());
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
        ], $c->getConnectionManager()->getServers());
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
        ], $c->getConnectionManager()->getServers());
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
            'QSCAN' => Command\QScan::class,
            'SHOW' => Command\Show::class
        ];

        $c = new MockClient();
        $commands = $c->getCommandHandlers();
        foreach ($commands as $command => $class) {
            $this->assertTrue(array_key_exists($command, $expectedCommands));
            $this->assertSame($expectedCommands[$command], $class);
        }
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
        $command = m::mock(CommandInterface::class)
            ->shouldReceive('setArguments')
            ->with(['id'])
            ->once()
            ->shouldReceive('parse')
            ->with('RESPONSE')
            ->andReturn('PARSED_RESPONSE')
            ->once()
            ->mock();

        $manager = m::mock(ManagerInterface::class)
            ->shouldReceive('execute')
            ->with($command)
            ->andReturn('RESPONSE')
            ->once()
            ->mock();

        $c = new MockClient();
        $c->setConnectionManager($manager);
        $c->setCommand('MYCOMMAND', $command);

        $result = $c->MyCommand('id');
        $this->assertSame('PARSED_RESPONSE', $result);
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