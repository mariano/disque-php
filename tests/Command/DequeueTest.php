<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\Dequeue;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class DequeueTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Dequeue();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: []');
        $c = new Dequeue();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [["test","stuff"]]');
        $c = new Dequeue();
        $c->build([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [128]');
        $c = new Dequeue();
        $c->build([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Dequeue: [""]');
        $c = new Dequeue();
        $c->build([""]);
    }

    public function testBuild()
    {
        $c = new Dequeue();
        $result = $c->build(['id']);
        $this->assertSame(['DEQUEUE', 'id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new Dequeue();
        $result = $c->build(['id', 'id2']);
        $this->assertSame(['DEQUEUE', 'id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Dequeue got: ["test"]');
        $c = new Dequeue();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Dequeue got: "test"');
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