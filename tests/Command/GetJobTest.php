<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandOptionException;
use Disque\Exception\InvalidCommandResponseException;

class GetJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new GetJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: []');
        $c = new GetJob();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsEmptyNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: [false,"test","stuff"]');
        $c = new GetJob();
        $c->build([false, 'test', 'stuff']);
    }

    public function testBuildInvalidArgumentsArrayNotLast()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: [{"count":10},"q"]');
        $c = new GetJob();
        $c->build([['count' => 10], 'q']);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff"}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff","count":10}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionCountNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":"stuff"}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['count' => 'stuff']]);
    }

    public function testBuildInvalidOptionCountNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":3.14}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['count' => 3.14]]);
    }

    public function testBuildInvalidOptionTimeoutNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":"stuff"}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['timeout' => 'stuff']]);
    }

    public function testBuildInvalidOptionTimeoutNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":3.14}');
        $c = new GetJob();
        $c->build(['q1', 'q2', ['timeout' => 3.14]]);
    }

    public function testBuild()
    {
        $c = new GetJob();
        $result = $c->build(['test', 'stuff']);
        $this->assertSame(['GETJOB', 'FROM', 'test', 'stuff'], $result);
    }

    public function testBuildOptionTimeout()
    {
        $c = new GetJob();
        $result = $c->build(['q1', 'q2', ['timeout' => 3000]]);
        $this->assertSame(['GETJOB', 'TIMEOUT', 3000, 'FROM', 'q1', 'q2'], $result);
    }

    public function testBuildOptionCount()
    {
        $c = new GetJob();
        $result = $c->build(['q1', 'q2', ['count' => 10]]);
        $this->assertSame(['GETJOB', 'COUNT', 10, 'FROM', 'q1', 'q2'], $result);
    }

    public function testBuildOptionTimeoutAndCount()
    {
        $c = new GetJob();
        $result = $c->build(['q1', 'q2', ['count' => 10, 'timeout' => 3000]]);
        $this->assertSame(['GETJOB', 'TIMEOUT', 3000, 'COUNT', 10, 'FROM', 'q1', 'q2'], $result);
    }

    public function testParseInvalidString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: "test"');
        $c = new GetJob();
        $c->parse('test');
    }

    public function testParseInvalidArrayEmpty()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: []');
        $c = new GetJob();
        $c->parse([]);
    }

    public function testParseInvalidArrayElementsNonArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: ["test","stuff"]');
        $c = new GetJob();
        $c->parse(['test', 'stuff']);
    }

    public function testParseInvalidArrayElementsSomeNonArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [["test","stuff","val"],"stuff"]');
        $c = new GetJob();
        $c->parse([['test', 'stuff', 'val'], 'stuff']);
    }

    public function testParseInvalidArrayElementsNon0()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"1":"test","2":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[1=>'test', 2=>'stuff', 3=>'val']]);
    }

    public function testParseInvalidArrayElementsNon1()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"0":"test","2":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[0=>'test', 2=>'stuff', 3=>'val']]);
    }

    public function testParseInvalidArrayElementsNon2()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"0":"test","1":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[0=>'test', 1=>'stuff', 3=>'val']]);
    }

    public function testParse()
    {
        $c = new GetJob();
        $result = $c->parse([['q', 'test', 'stuff']]);
        $this->assertSame([
            [
                'queue' => 'q',
                'id' => 'test',
                'body' => 'stuff'
            ]
        ], $result);
    }

    public function testParseMoreElements()
    {
        $c = new GetJob();
        $result = $c->parse([['q', 'test', 'stuff'], ['q2', 'test2', 'stuff2']]);
        $this->assertSame([
            [
                'queue' => 'q',
                'id' => 'test',
                'body' => 'stuff'
            ],
            [
                'queue' => 'q2',
                'id' => 'test2',
                'body' => 'stuff2'
            ]
        ], $result);
    }

}