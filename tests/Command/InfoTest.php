<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\Info;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class InfoTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Info();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Info();
        $result = $c->getCommand();
        $this->assertSame('INFO', $result);
    }

    public function testBuildInvalidArguments()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Info: ["test"]');
        $c = new Info();
        $c->setArguments(['test']);
    }

    public function testBuild()
    {
        $c = new Info();
        $c->setArguments([]);
        $result = $c->getArguments();
        $this->assertSame([], $result);
    }

    public function testParseInvalidNonString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Info got: ["test"]');
        $c = new Info();
        $c->parse(['test']);
    }

    public function testParse()
    {
        $c = new Info();
        $result = $c->parse('test');
        $this->assertSame('test', $result);
    }
}