<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Nack;
use Disque\Command\Response\InvalidResponseException;

class NackTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Nack();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Nack();
        $result = $c->getCommand();
        $this->assertSame('NACK', $result);
    }

    public function testIsBlocking()
    {
        $c = new Nack();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Nack: []');
        $c = new Nack();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Nack: [["test","stuff"]]');
        $c = new Nack();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Nack: [128]');
        $c = new Nack();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Nack: [""]');
        $c = new Nack();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new Nack();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new Nack();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Nack got: ["test"]');
        $c = new Nack();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Nack got: "test"');
        $c = new Nack();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new Nack();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}