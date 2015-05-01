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

    public function testBuildInvalidArguments()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Info: ["test"]');
        $c = new Info();
        $c->build(['test']);
    }

    public function testBuild()
    {
        $c = new Info();
        $result = $c->build([]);
        $this->assertSame(['INFO'], $result);
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