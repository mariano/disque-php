<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\Enqueue;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class EnqueueTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Enqueue();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: []');
        $c = new Enqueue();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [["test","stuff"]]');
        $c = new Enqueue();
        $c->build([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [128]');
        $c = new Enqueue();
        $c->build([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [""]');
        $c = new Enqueue();
        $c->build([""]);
    }

    public function testBuild()
    {
        $c = new Enqueue();
        $result = $c->build(['id']);
        $this->assertSame(['ENQUEUE', 'id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new Enqueue();
        $result = $c->build(['id', 'id2']);
        $this->assertSame(['ENQUEUE', 'id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Enqueue got: ["test"]');
        $c = new Enqueue();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Enqueue got: "test"');
        $c = new Enqueue();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new Enqueue();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}