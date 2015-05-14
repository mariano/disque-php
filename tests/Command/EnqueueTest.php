<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Enqueue;
use Disque\Command\Response\InvalidResponseException;

class EnqueueTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Enqueue();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Enqueue();
        $result = $c->getCommand();
        $this->assertSame('ENQUEUE', $result);
    }

    public function testIsBlocking()
    {
        $c = new Enqueue();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: []');
        $c = new Enqueue();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [["test","stuff"]]');
        $c = new Enqueue();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [128]');
        $c = new Enqueue();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Enqueue: [""]');
        $c = new Enqueue();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new Enqueue();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new Enqueue();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Enqueue got: ["test"]');
        $c = new Enqueue();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Enqueue got: "test"');
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