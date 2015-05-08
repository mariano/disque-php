<?php
namespace Disque\Test\Connection;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use Disque\Command;
use Disque\Connection\ConnectionException;
use Disque\Connection\ConnectionInterface;
use Disque\Connection\ResponseException;
use Disque\Connection\Socket;

class MockSocket extends Socket
{
    public function setSocket($socket)
    {
        $this->socket = $socket;
        $this->host = 'localhost';
        $this->port = 7711;
    }

    /**
     * Build actual socket
     *
     * @param string $host Host
     * @param int $port Port
     * @param float $timeout Timeout
     * @return resource Socket
     */
    protected function getSocket($host, $port, $timeout)
    {
        return $this->socket;
    }
}

class SocketTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testInstance()
    {
        $c = new Socket();
        $this->assertInstanceOf(ConnectionInterface::class, $c);
    }

    public function testConnectNoHost()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid host or port specified');
        $connection = new Socket();
        $connection->setHost(null);
        $connection->connect();
    }

    public function testConnectWrongHost()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid host or port specified');
        $connection = new Socket();
        $connection->setHost(128);
        $connection->connect();
    }

    public function testConnectNoPort()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid host or port specified');
        $connection = new Socket();
        $connection->setPort(null);
        $connection->connect();
    }

    public function testConnectWrongPort()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid host or port specified');
        $connection = new Socket();
        $connection->setPort('port');
        $connection->connect();
    }

    public function testIsConnectedFalse()
    {
        $connection = new Socket();
        $this->assertFalse($connection->isConnected());
    }

    public function testIsConnectedTrue()
    {
        $socket = fopen('php://memory','rw');
        $connection = new MockSocket();
        $connection->setSocket($socket);
        $this->assertTrue($connection->isConnected());
    }

    public function testDisconnectNotConnected()
    {
        $connection = new Socket();
        $this->assertFalse($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    public function testDisconnectConnected()
    {
        $socket = fopen('php://memory','rw');
        $connection = new MockSocket();
        $connection->setSocket($socket);
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    public function testConnectNoSocket()
    {
        $this->setExpectedException(ConnectionException::class, 'Could not connect to localhost:7711');
        $connection = new MockSocket();
        $connection->setSocket(null);
        $connection->connect();
    }

    public function testConnectStreamTimeout()
    {
        $socket = fopen('php://memory','rw');
        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->connect(['streamTimeout' => 3000]);
    }

    public function testSendErrorNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No connection established');

        $connection = new MockSocket();
        $connection->send("stuff");
    }

    public function testSendErrorInvalidData()
    {
        $this->setExpectedException(ConnectionException::class, 'Invalid data to be sent to client');

        $socket = fopen('php://memory','rw');
        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->send(['test' => 'stuff']);
    }

    public function testSend()
    {
        $socket = fopen('php://memory','rw');
        $connection = new MockSocket();
        $connection->setSocket($socket);

        rewind($socket);
        $data = fgets($socket);
        $this->assertFalse($data);

        $connection->send('HELLO');

        rewind($socket);
        $data = fgets($socket);
        $this->assertSame('HELLO', $data);
    }

    public function testReceiveErrorFromClient()
    {
        $this->setExpectedException(ResponseException::class, 'Error received from client: Error from Disque');

        $socket = fopen('php://memory','rw');
        fwrite($socket, "-Error from Disque\r\n");
        rewind($socket);

        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->receive();
    }

    public function testReceiveErrorNoConnection()
    {
        $this->setExpectedException(ConnectionException::class, 'No connection established');

        $connection = new MockSocket();
        $connection->receive();
    }

    public function testReceiveErrorNoType()
    {
        $this->setExpectedException(ConnectionException::class, 'Nothing received while reading from client');

        $socket = fopen('php://memory','rw');

        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->receive();
    }

    public function testReceiveErrorInvalidType()
    {
        $this->setExpectedException(ConnectionException::class, 'Don\'t know how to handle a response of type A');

        $socket = fopen('php://memory','rw');
        fwrite($socket, "A\r\n");
        rewind($socket);

        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->receive();
    }

    public function testReceiveErrorNoData()
    {
        $this->setExpectedException(ConnectionException::class, 'Nothing received while reading from client');

        $socket = fopen('php://memory','rw');
        fwrite($socket, "+");
        rewind($socket);

        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->receive();
    }

    public function testReceiveErrorEmptyString()
    {
        $this->setExpectedException(ConnectionException::class, 'Error while reading buffered string from client');

        $socket = fopen('php://memory','rw');
        fwrite($socket, "$0\r\n");
        rewind($socket);

        $connection = new MockSocket();
        $connection->setSocket($socket);
        $connection->receive();
    }

    /**
     * @dataProvider dataProviderForTestReceive
     */
    public function testReceive($data, $parsed)
    {
        $socket = fopen('php://memory','rw');
        fwrite($socket, "$data\r\n");
        rewind($socket);

        $connection = new MockSocket();
        $connection->setSocket($socket);

        $this->assertEquals($parsed, $connection->receive());
    }

    public static function dataProviderForTestReceive()
    {
        $longString = str_repeat('ABC', Socket::READ_BUFFER_LENGTH * 10);
        return [
            [
                'data' => '+',
                'parsed' => ''
            ],
            [
                'data' => '+PONG',
                'parsed' => 'PONG'
            ],
            [
                'data' => '+' . $longString,
                'parsed' => $longString
            ],
            [
                'data' => ':128',
                'parsed' => 128
            ],
            [
                'data' => ':3.14',
                'parsed' => 3
            ],
            [
                'data' => ':-128',
                'parsed' => -128
            ],
            [
                'data' => ':0',
                'parsed' => 0
            ],
            [
                'data' => ':',
                'parsed' => 0
            ],
            [
                'data' => '$-1',
                'parsed' => null
            ],
            [
                'data' => '$'.implode("\r\n", [
                    strlen('hello'),
                    'hello'
                ]),
                'parsed' => 'hello'
            ],
            [
                'data' => '$'.implode("\r\n", [
                    strlen("hello\nworld"),
                    "hello\nworld"
                ]),
                'parsed' => "hello\nworld"
            ],
            [
                'data' => '$'.implode("\r\n", [
                    strlen($longString),
                    $longString
                ]),
                'parsed' => $longString
            ],
            [
                'data' => '$'.implode("\r\n", [
                    strlen("{$longString}\r\n{$longString}\n{$longString}"),
                    "{$longString}\r\n{$longString}\n{$longString}"
                ]),
                'parsed' => "{$longString}\r\n{$longString}\n{$longString}"
            ],
            [
                'data' => '*-1',
                'parsed' => null
            ],
            [
                'data' => '*0',
                'parsed' => []
            ],
            [
                'data' => "*1\r\n" . implode("\r\n", [
                    '+pong'
                ]),
                'parsed' => [
                    'pong'
                ]
            ],
            [
                'data' => "*2\r\n" . implode("\r\n", [
                    '+hello',
                    '+world'
                ]),
                'parsed' => [
                    'hello',
                    'world'
                ]
            ],
            [
                'data' => "*3\r\n" . implode("\r\n", [
                    '+hello',
                    '+world',
                    ':128'
                ]),
                'parsed' => [
                    'hello',
                    'world',
                    128
                ]
            ],
            [
                'data' => "*4\r\n" . implode("\r\n", [
                    '+hello',
                    '+world',
                    '*-1',
                    ':128'
                ]),
                'parsed' => [
                    'hello',
                    'world',
                    null,
                    128
                ]
            ],
            [
                'data' => "*4\r\n" . implode("\r\n", [
                    '+hello',
                    '+world',
                    "*3\r\n" . implode("\r\n", [
                        '$'.implode("\r\n", [
                            strlen($longString),
                            $longString
                        ]),
                        ':5',
                        '+BYE'
                    ]),
                    ':128'
                ]),
                'parsed' => [
                    'hello',
                    'world',
                    [
                        $longString,
                        5,
                        'BYE'
                    ],
                    128
                ]
            ],
        ];
    }

    public function testExecuteAck()
    {
        $command = new Command\AckJob();
        $command->setArguments(['id']);

        $connection = m::mock(MockSocket::class)
            ->makePartial()
            ->shouldReceive('send')
            ->with("*2\r\n$6\r\nACKJOB\r\n$2\r\nid\r\n")
            ->once()
            ->shouldReceive('receive')
            ->andReturn(['result' => true])
            ->once()
            ->mock();
        $connection->setSocket(fopen('php://memory','rw'));
        $result = $connection->execute($command);
        $this->assertSame(['result' => true], $result);
    }

    public function testExecuteAddUnicode()
    {
        $command = new Command\AddJob();
        $command->setArguments(['queue', '大']);

        $connection = m::mock(MockSocket::class)
            ->makePartial()
            ->shouldReceive('send')
            ->with(implode("\r\n", [
                '*4',
                '$6',
                'ADDJOB',
                '$5',
                'queue',
                '$' . strlen('大'),
                '大',
                '$1',
                '0'
            ]) . "\r\n")
            ->once()
            ->shouldReceive('receive')
            ->andReturn(['result' => true])
            ->once()
            ->mock();

        $connection->setSocket(fopen('php://memory','rw'));
        $result = $connection->execute($command);
        $this->assertSame(['result' => true], $result);
    }

    public function testExecuteHello()
    {
        $command = new Command\Hello();

        $connection = m::mock(MockSocket::class)
            ->makePartial()
            ->shouldReceive('send')
            ->with("*1\r\n$5\r\nHELLO\r\n")
            ->once()
            ->shouldReceive('receive')
            ->andReturn(['result' => true])
            ->once()
            ->mock();

        $connection->setSocket(fopen('php://memory','rw'));
        $result = $connection->execute($command);
        $this->assertSame(['result' => true], $result);
    }
}