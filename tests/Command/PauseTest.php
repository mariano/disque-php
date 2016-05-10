<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Pause;
use Disque\Command\Response\InvalidResponseException;

class PauseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Pause();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Pause();
        $result = $c->getCommand();
        $this->assertSame('PAUSE', $result);
    }

    public function testIsBlocking()
    {
        $c = new Pause();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Pause: []');
        $c = new Pause();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Pause: [["test","stuff"]]');
        $c = new Pause();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Pause: [128]');
        $c = new Pause();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Pause: [""]');
        $c = new Pause();
        $c->setArguments([""]);
    }

    public function testBuildInvalidArgumentsNoOptions()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Pause: ["queue"]');
        $c = new Pause();
        $c->setArguments(['queue']);
    }

    public function testBuildWithOptions()
    {
        $c = new Pause();
        $c->setArguments(['queue', 'in']);
        $result = $c->getArguments();
        $this->assertSame(['queue', 'in'], $result);
    }

    public function testParseInvalidNoStringArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Pause got: ["test"]');
        $c = new Pause();
        $c->parse(['test']);
    }

    public function testParseInvalidNonString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Pause got: 128');
        $c = new Pause();
        $c->parse(128);
    }

    public function testParse()
    {
        $c = new Pause();
        $result = $c->parse('none');
        $this->assertSame('none', $result);
    }
}