<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\AddJob;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandOptionException;
use Disque\Exception\InvalidCommandResponseException;

class AddJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new AddJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: []');
        $c = new AddJob();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsTooFew()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test"]');
        $c = new AddJob();
        $c->build(['test']);
    }

    public function testBuildInvalidArgumentsTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test","stuff","more","elements"]');
        $c = new AddJob();
        $c->build(['test','stuff','more','elements']);
    }

    public function testBuildInvalidArguments0NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"1":"test","2":"stuff"}');
        $c = new AddJob();
        $c->build([1=>'test', 2=>'stuff']);
    }

    public function testBuildInvalidArguments1NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"0":"test","2":"stuff"}');
        $c = new AddJob();
        $c->build([0=>'test', 2=>'stuff']);
    }

    public function testBuildInvalidArguments0NonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: [true,"stuff"]');
        $c = new AddJob();
        $c->build([true, "stuff"]);
    }

    public function testBuildInvalidArguments1NonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test",10]');
        $c = new AddJob();
        $c->build(["test", 10]);
    }

    public function testBuildInvalidArguments2NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"0":"test","1":"stuff","3":{"timeout":3000}}');
        $c = new AddJob();
        $c->build([0=>'test', 1=>'stuff', 3=>['timeout' => 3000]]);
    }

    public function testBuildInvalidArguments2NonArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test","stuff","more"]');
        $c = new AddJob();
        $c->build(['test', 'stuff', 'more']);
    }

    public function testBuildInvalidArguments2EmptyArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test","stuff",[]]');
        $c = new AddJob();
        $c->build(['test', 'stuff', []]);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"test":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"test":"stuff","count":10}');
        $c = new AddJob();
        $c->build(['q', 'j', ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionTimeoutNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"timeout":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['timeout' => 'stuff']]);
    }

    public function testBuildInvalidOptionTimeoutNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"timeout":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['timeout' => 3.14]]);
    }

    public function testBuildInvalidOptionReplicateNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"replicate":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['replicate' => 'stuff']]);
    }

    public function testBuildInvalidOptionReplicateNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"replicate":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['replicate' => 3.14]]);
    }

    public function testBuildInvalidOptionDelayNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"delay":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['delay' => 'stuff']]);
    }

    public function testBuildInvalidOptionDelayNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"delay":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['delay' => 3.14]]);
    }

    public function testBuildInvalidOptionRetryNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"retry":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['retry' => 'stuff']]);
    }

    public function testBuildInvalidOptionRetryNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"retry":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['retry' => 3.14]]);
    }

    public function testBuildInvalidOptionTtlNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"ttl":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['ttl' => 'stuff']]);
    }

    public function testBuildInvalidOptionTtlNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"ttl":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['ttl' => 3.14]]);
    }

    public function testBuildInvalidOptionMaxlenNonNumeric()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"maxlen":"stuff"}');
        $c = new AddJob();
        $c->build(['q', 'j', ['maxlen' => 'stuff']]);
    }

    public function testBuildInvalidOptionMaxlenNonInt()
    {
        $this->setExpectedException(InvalidCommandOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"maxlen":3.14}');
        $c = new AddJob();
        $c->build(['q', 'j', ['maxlen' => 3.14]]);
    }

    public function testBuild()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job']);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0], $result);
    }

    public function testBuildOptionTimeout()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['timeout' => 3000]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 3000], $result);
    }

    public function testBuildOptionAsync()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['async' => true]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'ASYNC'], $result);
    }

    public function testBuildOptionAsyncAndTimeout()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['async' => true, 'timeout' => 3000]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 3000, 'ASYNC'], $result);
    }

    public function testBuildOptionReplicate()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['replicate' => 5]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'REPLICATE', 5], $result);
    }

    public function testBuildOptionDelay()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['delay' => 3000]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'DELAY', 3000], $result);
    }

    public function testBuildOptionRetry()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['retry' => 3]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'RETRY', 3], $result);
    }

    public function testBuildOptionTtl()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['ttl' => 5000]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'TTL', 5000], $result);
    }

    public function testBuildOptionMaxlen()
    {
        $c = new AddJob();
        $result = $c->build(['queue', 'job', ['maxlen' => 5]]);
        $this->assertSame(['ADDJOB', 'queue', 'job', 0, 'MAXLEN', 5], $result);
    }

    public function testParseInvalidNonString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\AddJob got: 10');
        $c = new AddJob();
        $c->parse(10);
    }

    public function testParseInvalidNonStringBoolTrue()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\AddJob got: true');
        $c = new AddJob();
        $c->parse(true);
    }

    public function testParse()
    {
        $c = new AddJob();
        $result = $c->parse('id');
        $this->assertSame('id', $result);
    }

    public function testParseFalse()
    {
        $c = new AddJob();
        $result = $c->parse(false);
        $this->assertNull($result);
    }
}