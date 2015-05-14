<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\FastAck;
use Disque\Command\Response\InvalidResponseException;

class FastAckTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new FastAck();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new FastAck();
        $result = $c->getCommand();
        $this->assertSame('FASTACK', $result);
    }

    public function testIsBlocking()
    {
        $c = new FastAck();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: []');
        $c = new FastAck();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [["test","stuff"]]');
        $c = new FastAck();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [128]');
        $c = new FastAck();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\FastAck: [""]');
        $c = new FastAck();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new FastAck();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new FastAck();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\FastAck got: ["test"]');
        $c = new FastAck();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\FastAck got: "test"');
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