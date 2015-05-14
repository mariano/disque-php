<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Show;
use Disque\Command\Response\InvalidResponseException;

class ShowTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Show();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Show();
        $result = $c->getCommand();
        $this->assertSame('SHOW', $result);
    }

    public function testIsBlocking()
    {
        $c = new Show();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Show: []');
        $c = new Show();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Show: ["test","stuff"]');
        $c = new Show();
        $c->setArguments(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Show: {"test":"stuff"}');
        $c = new Show();
        $c->setArguments(['test' => 'stuff']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Show: {"1":"stuff"}');
        $c = new Show();
        $c->setArguments([1 => 'stuff']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Show: [false]');
        $c = new Show();
        $c->setArguments([false]);
    }

    public function testBuild()
    {
        $c = new Show();
        $c->setArguments(['test']);
        $result = $c->getArguments();
        $this->assertSame(['test'], $result);
    }

    public function testParseInvalidNonArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Show got: "test"');
        $c = new Show();
        $c->parse('test');
    }

    public function testParseInvalidEmptyArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Show got: []');
        $c = new Show();
        $c->parse([]);
    }

    public function testParseInvalidOddArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Show got: ["odd","elements","array"]');
        $c = new Show();
        $c->parse(['odd','elements','array']);
    }

    public function testParseEmpty()
    {
        $c = new Show();
        $result = $c->parse(false);
        $this->assertNull($result);
    }

    public function testParse()
    {
        $c = new Show();
        $result = $c->parse([
            'key1',
            'value1',
            'key2',
            'value2'
        ]);
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2'
        ], $result);
    }
}