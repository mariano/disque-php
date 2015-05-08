<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Dequeue;
use Disque\Command\Response\InvalidResponseException;

class DequeueTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Dequeue();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Dequeue();
        $result = $c->getCommand();
        $this->assertSame('DEQUEUE', $result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: []');
        $c = new Dequeue();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [["test","stuff"]]');
        $c = new Dequeue();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [128]');
        $c = new Dequeue();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [""]');
        $c = new Dequeue();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new Dequeue();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new Dequeue();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Dequeue got: ["test"]');
        $c = new Dequeue();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Dequeue got: "test"');
        $c = new Dequeue();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new Dequeue();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}