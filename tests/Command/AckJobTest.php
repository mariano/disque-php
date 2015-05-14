<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\AckJob;
use Disque\Command\Response\InvalidResponseException;

class AckJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new AckJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new AckJob();
        $result = $c->getCommand();
        $this->assertSame('ACKJOB', $result);
    }

    public function testIsBlocking()
    {
        $c = new AckJob();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: []');
        $c = new AckJob();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [["test","stuff"]]');
        $c = new AckJob();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [128]');
        $c = new AckJob();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [""]');
        $c = new AckJob();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new AckJob();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new AckJob();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\AckJob got: ["test"]');
        $c = new AckJob();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\AckJob got: "test"');
        $c = new AckJob();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new AckJob();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}