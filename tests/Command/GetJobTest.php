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

    public function testGetCommand()
    {
        $c = new GetJob();
        $result = $c->getCommand();
        $this->assertSame('GETJOB', $result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: []');
        $c = new GetJob();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: [false,"test","stuff"]');
        $c = new GetJob();
        $c->setArguments([false, 'test', 'stuff']);
    }

    public function testBuildInvalidArgumentsArrayNotLast()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\GetJob: [{"count":10},"q"]');
        $c = new GetJob();
        $c->setArguments([['count' => 10], 'q']);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff","count":10}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionCountNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 'stuff']]);
    }

    public function testBuildInvalidOptionCountNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":3.14}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 3.14]]);
    }

    public function testBuildInvalidOptionTimeoutNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['timeout' => 'stuff']]);
    }

    public function testBuildInvalidOptionTimeoutNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":3.14}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['timeout' => 3.14]]);
    }

    public function testBuild()
    {
        $c = new GetJob();
        $c->setArguments(['test', 'stuff']);
        $result = $c->getArguments();
        $this->assertSame(['FROM', 'test', 'stuff'], $result);
    }

    public function testBuildOptionTimeout()
    {
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['timeout' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame(['TIMEOUT', 3000, 'FROM', 'q1', 'q2'], $result);
    }

    public function testBuildOptionCount()
    {
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 10]]);
        $result = $c->getArguments();
        $this->assertSame(['COUNT', 10, 'FROM', 'q1', 'q2'], $result);
    }

    public function testBuildOptionTimeoutAndCount()
    {
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 10, 'timeout' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame(['TIMEOUT', 3000, 'COUNT', 10, 'FROM', 'q1', 'q2'], $result);
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
        $result = $c->parse([['q', 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'stuff']]);
        $this->assertSame([
            [
                'queue' => 'q',
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'stuff'
            ]
        ], $result);
    }

    public function testParseUnicode()
    {
        $c = new GetJob();
        $result = $c->parse([['queue', 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', '大']]);
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => '大'
            ]
        ], $result);
    }
}