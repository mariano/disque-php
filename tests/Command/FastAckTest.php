<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\FastAck;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class FastAckTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new FastAck();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: []');
        $c = new FastAck();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [["test","stuff"]]');
        $c = new FastAck();
        $c->build([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [128]');
        $c = new FastAck();
        $c->build([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [""]');
        $c = new FastAck();
        $c->build([""]);
    }

    public function testBuild()
    {
        $c = new FastAck();
        $result = $c->build(['id']);
        $this->assertSame(['FASTACK', 'id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new FastAck();
        $result = $c->build(['id', 'id2']);
        $this->assertSame(['FASTACK', 'id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\FastAck got: ["test"]');
        $c = new FastAck();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\FastAck got: "test"');
        $c = new FastAck();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new FastAck();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}