<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Info;
use Disque\Command\Response\InvalidResponseException;

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

    public function testIsBlocking()
    {
        $c = new Info();
        $result = $c->isBlocking();
        $this->assertFalse($result);
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
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Info got: ["test"]');
        $c = new Info();
        $c->parse(['test']);
    }

    public function testParse()
    {
        $c = new Info();
        $result = $c->parse('test');
        $this->assertSame([], $result);
    }

    public function testParseCategories()
    {
        $c = new Info();
        $result = $c->parse("# Category\r\na:b\r\nfoo:bar\r\n\r\n# Baz\r\nwoop:shawoop\r\n");
        $this->assertSame([
            'Category' => [
                'a' => 'b',
                'foo' => 'bar',
            ],
            'Baz' => [
                'woop' => 'shawoop',
            ],
        ], $result);
    }
}