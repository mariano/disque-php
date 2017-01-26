<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\CommandInterface;
use Disque\Command\GetJob;
use Disque\Command\Response\InvalidResponseException;
use Disque\Command\Response\JobsResponse AS Response;
use Disque\Command\Response\JobsWithQueueResponse AS Queue;
use Disque\Command\Response\JobsWithCountersResponse AS Counters;

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

    public function testIsBlocking()
    {
        $c = new GetJob();
        $result = $c->isBlocking();
        $this->assertTrue($result);
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
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"test":"stuff","count":10}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionCountNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 'stuff']]);
    }

    public function testBuildInvalidOptionCountNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"count":3.14}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['count' => 3.14]]);
    }

    public function testBuildInvalidOptionTimeoutNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":"stuff"}');
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['timeout' => 'stuff']]);
    }

    public function testBuildInvalidOptionTimeoutNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\GetJob: {"timeout":3.14}');
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

    public function testBuildOptionWithCounters()
    {
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['withcounters' => true]]);
        $result = $c->getArguments();
        $this->assertSame(['WITHCOUNTERS', 'FROM', 'q1', 'q2'], $result);
    }

    public function testBuildOptionWithNohang()
    {
        $c = new GetJob();
        $c->setArguments(['q1', 'q2', ['nohang' => true]]);
        $result = $c->getArguments();
        $this->assertSame(['NOHANG', 'FROM', 'q1', 'q2'], $result);
    }

    public function testParseInvalidString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: "test"');
        $c = new GetJob();
        $c->parse('test');
    }

    public function testParseInvalidArrayElementsNonArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: ["test","stuff"]');
        $c = new GetJob();
        $c->parse(['test', 'stuff']);
    }

    public function testParseInvalidArrayElementsSomeNonArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [["test","stuff","val"],"stuff"]');
        $c = new GetJob();
        $c->parse([['test', 'stuff', 'val'], 'stuff']);
    }

    public function testParseInvalidArrayElementsNon0()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"1":"test","2":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[1=>'test', 2=>'stuff', 3=>'val']]);
    }

    public function testParseInvalidArrayElementsNon1()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"0":"test","2":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[0=>'test', 2=>'stuff', 3=>'val']]);
    }

    public function testParseInvalidArrayElementsNon2()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\GetJob got: [{"0":"test","1":"stuff","3":"val"}]');
        $c = new GetJob();
        $c->parse([[0=>'test', 1=>'stuff', 3=>'val']]);
    }

    public function testParse()
    {
        $c = new GetJob();
        $queue = 'q';
        $id = 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body = 'lorem ipsum';
        $response = [$queue, $id, $body];
        $parsedResponse = $c->parse([$response]);

        $this->assertSame([
            [
                Queue::KEY_QUEUE => $queue,
                Response::KEY_ID  => $id,
                Response::KEY_BODY => $body,
            ]
        ], $parsedResponse);
    }

    public function testParseUnicode()
    {
        $c = new GetJob();

        $queue = 'q';
        $id = 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body = '大';
        $response = [$queue, $id, $body];
        $parsedResponse = $c->parse([$response]);

        $this->assertSame([
            [
                Queue::KEY_QUEUE => $queue,
                Response::KEY_ID  => $id,
                Response::KEY_BODY => $body,
            ]
        ], $parsedResponse);
    }

    public function testParseWithCounters()
    {
        $c = new GetJob();
        // Set the responseHandler to JobsWithCountersResponse
        $c->setArguments(['q1', ['withcounters' => true]]);

        $queue = 'q';
        $id = 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body = 'lorem ipsum';
        $nacks = 1;
        $ad = 2;
        $response = [$queue, $id, $body, 'nacks', $nacks, 'additional-deliveries', $ad];
        $parsedResponse = $c->parse([$response]);

        $this->assertSame([
            [
                Queue::KEY_QUEUE => $queue,
                Response::KEY_ID  => $id,
                Response::KEY_BODY => $body,
                Counters::KEY_NACKS => $nacks,
                Counters::KEY_ADDITIONAL_DELIVERIES => $ad
            ]
        ], $parsedResponse);
    }
}
