<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\QLen;
use Disque\Command\Response\InvalidResponseException;

class QLenTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new QLen();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new QLen();
        $result = $c->getCommand();
        $this->assertSame('QLEN', $result);
    }

    public function testIsBlocking()
    {
        $c = new QLen();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: []');
        $c = new QLen();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: ["test","stuff"]');
        $c = new QLen();
        $c->setArguments(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: {"test":"stuff"}');
        $c = new QLen();
        $c->setArguments(['test' => 'stuff']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: {"1":"stuff"}');
        $c = new QLen();
        $c->setArguments([1 => 'stuff']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: [false]');
        $c = new QLen();
        $c->setArguments([false]);
    }

    public function testBuild()
    {
        $c = new QLen();
        $c->setArguments(['test']);
        $result = $c->getArguments();
        $this->assertSame(['test'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\QLen got: ["test"]');
        $c = new QLen();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\QLen got: "test"');
        $c = new QLen();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new QLen();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}