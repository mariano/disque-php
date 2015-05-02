<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\AckJob;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class AckJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new AckJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: []');
        $c = new AckJob();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [["test","stuff"]]');
        $c = new AckJob();
        $c->build([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [128]');
        $c = new AckJob();
        $c->build([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\AckJob: [""]');
        $c = new AckJob();
        $c->build([""]);
    }

    public function testBuild()
    {
        $c = new AckJob();
        $result = $c->build(['id']);
        $this->assertSame(['ACKJOB', 'id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new AckJob();
        $result = $c->build(['id', 'id2']);
        $this->assertSame(['ACKJOB', 'id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\AckJob got: ["test"]');
        $c = new AckJob();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\AckJob got: "test"');
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