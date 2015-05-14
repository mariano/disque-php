<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\AddJob;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\CommandInterface;
use Disque\Command\Response\InvalidResponseException;

class AddJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new AddJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new AddJob();
        $result = $c->getCommand();
        $this->assertSame('ADDJOB', $result);
    }

    public function testIsBlocking()
    {
        $c = new AddJob();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: []');
        $c = new AddJob();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsTooFew()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test"]');
        $c = new AddJob();
        $c->setArguments(['test']);
    }

    public function testBuildInvalidArgumentsTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test","stuff","more","elements"]');
        $c = new AddJob();
        $c->setArguments(['test','stuff','more','elements']);
    }

    public function testBuildInvalidArguments0NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"1":"test","2":"stuff"}');
        $c = new AddJob();
        $c->setArguments([1=>'test', 2=>'stuff']);
    }

    public function testBuildInvalidArguments1NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"0":"test","2":"stuff"}');
        $c = new AddJob();
        $c->setArguments([0=>'test', 2=>'stuff']);
    }

    public function testBuildInvalidArguments0NonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: [true,"stuff"]');
        $c = new AddJob();
        $c->setArguments([true, "stuff"]);
    }

    public function testBuildInvalidArguments1NonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test",10]');
        $c = new AddJob();
        $c->setArguments(["test", 10]);
    }

    public function testBuildInvalidArguments2NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: {"0":"test","1":"stuff","3":{"timeout":3000}}');
        $c = new AddJob();
        $c->setArguments([0=>'test', 1=>'stuff', 3=>['timeout' => 3000]]);
    }

    public function testBuildInvalidArguments2NonArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AddJob: ["test","stuff","more"]');
        $c = new AddJob();
        $c->setArguments(['test', 'stuff', 'more']);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"test":"stuff"}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"test":"stuff","count":10}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionTimeoutNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"timeout":"stuff"}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['timeout' => 'stuff']]);
    }

    public function testBuildInvalidOptionTimeoutNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"timeout":3.14}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['timeout' => 3.14]]);
    }

    public function testBuildInvalidOptionReplicateNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"replicate":"stuff","timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['replicate' => 'stuff']]);
    }

    public function testBuildInvalidOptionReplicateNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"replicate":3.14,"timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['replicate' => 3.14]]);
    }

    public function testBuildInvalidOptionDelayNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"delay":"stuff","timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['delay' => 'stuff']]);
    }

    public function testBuildInvalidOptionDelayNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"delay":3.14,"timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['delay' => 3.14]]);
    }

    public function testBuildInvalidOptionRetryNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"retry":"stuff","timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['retry' => 'stuff']]);
    }

    public function testBuildInvalidOptionRetryNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"retry":3.14,"timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['retry' => 3.14]]);
    }

    public function testBuildInvalidOptionTtlNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"ttl":"stuff","timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['ttl' => 'stuff']]);
    }

    public function testBuildInvalidOptionTtlNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"ttl":3.14,"timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['ttl' => 3.14]]);
    }

    public function testBuildInvalidOptionMaxlenNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"maxlen":"stuff","timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['maxlen' => 'stuff']]);
    }

    public function testBuildInvalidOptionMaxlenNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\AddJob: {"maxlen":3.14,"timeout":0}');
        $c = new AddJob();
        $c->setArguments(['q', 'j', ['maxlen' => 3.14]]);
    }

    public function testBuild()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job']);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0], $result);
    }

    public function testBuildEmptyOptions()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', []]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0], $result);
    }

    public function testBuildOptionTimeout()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['timeout' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 3000], $result);
    }

    public function testBuildOptionAsync()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['async' => true]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'ASYNC'], $result);
    }

    public function testBuildOptionAsyncAndTimeout()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['async' => true, 'timeout' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 3000, 'ASYNC'], $result);
    }

    public function testBuildOptionReplicate()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['replicate' => 5]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'REPLICATE', 5], $result);
    }

    public function testBuildOptionDelay()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['delay' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'DELAY', 3000], $result);
    }

    public function testBuildOptionRetry()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['retry' => 3]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'RETRY', 3], $result);
    }

    public function testBuildOptionTtl()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['ttl' => 5000]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'TTL', 5000], $result);
    }

    public function testBuildOptionMaxlen()
    {
        $c = new AddJob();
        $c->setArguments(['queue', 'job', ['maxlen' => 5]]);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'job', 0, 'MAXLEN', 5], $result);
    }

    public function testBuildUnicode()
    {
        $c = new AddJob();
        $c->setArguments(['queue', '大']);
        $result = $c->getArguments();
        $this->assertSame(['queue', '大', 0], $result);
    }

    public function testParseInvalidNonString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\AddJob got: 10');
        $c = new AddJob();
        $c->parse(10);
    }

    public function testParseInvalidNonStringBoolTrue()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\AddJob got: true');
        $c = new AddJob();
        $c->parse(true);
    }

    public function testParse()
    {
        $c = new AddJob();
        $result = $c->parse('id');
        $this->assertSame('id', $result);
    }
}