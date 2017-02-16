<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\JScan;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\CommandInterface;
use Disque\Command\Response\InvalidResponseException;

class JScanTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new JScan();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new JScan();
        $result = $c->getCommand();
        $this->assertSame('JSCAN', $result);
    }

    public function testIsBlocking()
    {
        $c = new JScan();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\JScan: [0,{"count":10},["stuff"]]');
        $c = new JScan();
        $c->setArguments([0, ['count' => 10], ['stuff']]);
    }

    public function testBuildInvalidArgument0NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\JScan: {"2":0,"1":{"count":10}}');
        $c = new JScan();
        $c->setArguments([2=>0, 1=>['count' => 10]]);
    }

    public function testBuildInvalidCursorNotNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\JScan: ["test"]');
        $c = new JScan();
        $c->setArguments(['test']);
    }

    public function testBuildInvalidArgument1NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\JScan: {"0":0,"2":{"count":10}}');
        $c = new JScan();
        $c->setArguments([0=>0, 2=>['count' => 10]]);
    }

    public function testBuildInvalidArgumentOptionsNonArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\JScan: [0,"test"]');
        $c = new JScan();
        $c->setArguments([0, 'test']);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"test":"stuff"}');
        $c = new JScan();
        $c->setArguments([0, ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"test":"stuff","count":10}');
        $c = new JScan();
        $c->setArguments([0, ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionCountNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"count":"stuff"}');
        $c = new JScan();
        $c->setArguments([0, ['count' => 'stuff']]);
    }

    public function testBuildInvalidOptionCountNonInt()
    {
        $this->setExpectedExceptionRegExp(InvalidOptionException::class, '/^Invalid command options. Options for command Disque\\\\Command\\\\JScan: {"count":3.14\d*}$/');
        $c = new JScan();
        $c->setArguments([0, ['count' => 3.14]]);
    }

    public function testBuildInvalidOptionQueueNonString()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"queue":["urgent","low"]}');
        $c = new JScan();
        $c->setArguments([0, ['queue' => ["urgent", "low"]]]);
    }

    public function testBuildInvalidOptionStateNonArray()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"state":"acked"}');
        $c = new JScan();
        $c->setArguments([0, ['state' => 'acked']]);
    }

    public function testBuildInvalidOptionReplyNonString()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\JScan: {"reply":10}');
        $c = new JScan();
        $c->setArguments([0, ['reply' => 10]]);
    }

    public function testBuildNoArguments()
    {
        $c = new JScan();
        $c->setArguments([]);
        $result = $c->getArguments();
        $this->assertSame([0], $result);
    }

    public function testBuildOptionCount()
    {
        $c = new JScan();
        $c->setArguments([0, ['count' => 10]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'COUNT', 10], $result);
    }

    public function testBuildOptionBusyloop()
    {
        $c = new JScan();
        $c->setArguments([0, ['busyloop' => true]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'BUSYLOOP'], $result);
    }

    public function testBuildOptionBusyloopAndCount()
    {
        $c = new JScan();
        $c->setArguments([0, ['busyloop' => true, 'count' => 10]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'BUSYLOOP', 'COUNT', 10], $result);
    }

    public function testBuildOptionQueue()
    {
        $c = new JScan();
        $c->setArguments([0, ['queue' => 'urgent']]);
        $result = $c->getArguments();
        $this->assertSame([0, 'QUEUE', 'urgent'], $result);
    }

    public function testBuildOptionState()
    {
        $c = new JScan();
        $c->setArguments([0, ['state' => ['wait-repl', 'active', 'queued', 'acked']]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'STATE', 'wait-repl', 'STATE', 'active', 'STATE', 'queued', 'STATE', 'acked'], $result);
    }

    public function testBuildOptionReplyAll()
    {
        $c = new JScan();
        $c->setArguments([0, ['reply' => 'all']]);
        $result = $c->getArguments();
        $this->assertSame([0, 'REPLY', 'all'], $result);
    }

    public function testBuildCursor()
    {
        $c = new JScan();
        $c->setArguments([1]);
        $result = $c->getArguments();
        $this->assertSame([1], $result);
    }

    public function testBuildCursorAndOptions()
    {
        $c = new JScan();
        $c->setArguments([1, ['count' => 10, 'queue' => 'urgent']]);
        $result = $c->getArguments();
        $this->assertSame([1, 'COUNT', 10, 'QUEUE', 'urgent'], $result);
    }

    public function testParseInvalidNonArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\JScan got: 10');
        $c = new JScan();
        $c->parse(10);
    }

    public function testParseWithNextCursor()
    {
        $c = new JScan();
        $result = $c->parse(['1', ['D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1', 'D-19e14a24-dozLy1rAQPKa2kwIyEDnp5bT-05a1']]);
        $this->assertSame([
            'finished' => false,
            'nextCursor' => 1,
            'jobs' => [
                ['id' => 'D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1'],
                ['id' => 'D-19e14a24-dozLy1rAQPKa2kwIyEDnp5bT-05a1']
            ]
        ], $result);
    }

    public function testParseFinished()
    {
        $c = new JScan();
        $result = $c->parse(['0', ['D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1', 'D-19e14a24-dozLy1rAQPKa2kwIyEDnp5bT-05a1']]);
        $this->assertSame([
            'finished' => true,
            'nextCursor' => 0,
            'jobs' => [
                ['id' => 'D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1'],
                ['id' => 'D-19e14a24-dozLy1rAQPKa2kwIyEDnp5bT-05a1']
            ]
        ], $result);
    }

    public function testParseDetailed()
    {
        $c = new JScan();
        $result = $c->parse(['0', [
            [
                'id',
                'D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1',
                'queue',
                'urgent',
                'state',
                'queued',
                'repl',
                2,
                'ttl',
                81110,
                'ctime',
                1462891579255000000,
                'delay',
                0,
                'retry',
                3,
                'nacks',
                0,
                'additional-deliveries',
                0,
                'nodes-delivered',
                [
                    '19e14a243511654800d052e0c6fefb54b955e59c',
                    '01832ef70ba88ff7dc935731fe35841612a9edf8'
                ],
                'nodes-confirmed',
                [],
                'next-requeue-within',
                784,
                'next-awake-within',
                284,
                'body',
                '{"name":"Claudia"}'
            ]
        ]]);

        $this->assertSame([
            'finished' => true,
            'nextCursor' => 0,
            'jobs' => [
                [
                    'id' => 'D-01832ef7-7WpBQYzbmzaDw1QKykdGxlUi-05a1',
                    'queue' => 'urgent',
                    'state' => 'queued',
                    'repl' => 2,
                    'ttl' => 81110,
                    'ctime' => 1462891579255000000,
                    'delay' => 0,
                    'retry' => 3,
                    'nacks' => 0,
                    'additional-deliveries' => 0,
                    'nodes-delivered' => [
                        '19e14a243511654800d052e0c6fefb54b955e59c',
                        '01832ef70ba88ff7dc935731fe35841612a9edf8'
                    ],
                    'nodes-confirmed' => [],
                    'next-requeue-within' => 784,
                    'next-awake-within' => 284,
                    'body' => '{"name":"Claudia"}'
                ],
            ]
        ], $result);
    }

}