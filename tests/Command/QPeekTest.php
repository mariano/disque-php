<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\QPeek;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class QPeekTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new QPeek();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new QPeek();
        $result = $c->getCommand();
        $this->assertSame('QPEEK', $result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: []');
        $c = new QPeek();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: ["test","stuff","arg"]');
        $c = new QPeek();
        $c->setArguments(['test', 'stuff', 'arg']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: {"test":"stuff","arg":"val"}');
        $c = new QPeek();
        $c->setArguments(['test' => 'stuff', 'arg' => 'val']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: {"1":"stuff","2":"test"}');
        $c = new QPeek();
        $c->setArguments([1 => 'stuff', 2 => 'test']);
    }

    public function testBuildInvalidArgumentsNumericNon1()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: {"0":"stuff","2":"test"}');
        $c = new QPeek();
        $c->setArguments([0 => 'stuff', 2 => 'test']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: [false,"test"]');
        $c = new QPeek();
        $c->setArguments([false, 'test']);
    }

    public function testBuildInvalidArgumentsNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: ["test","stuff"]');
        $c = new QPeek();
        $c->setArguments(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsNonInt()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QPeek: ["test",3.14]');
        $c = new QPeek();
        $c->setArguments(['test', 3.14]);
    }

    public function testBuild()
    {
        $c = new QPeek();
        $c->setArguments(['test', 78]);
        $result = $c->getArguments();
        $this->assertSame(['test', 78], $result);
    }

    public function testParseInvalidString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: "test"');
        $c = new QPeek();
        $c->parse('test');
    }

    public function testParseInvalidArrayEmpty()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: []');
        $c = new QPeek();
        $c->parse([]);
    }

    public function testParseInvalidArrayElementsNonArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: ["test","stuff"]');
        $c = new QPeek();
        $c->parse(['test', 'stuff']);
    }

    public function testParseInvalidArrayElementsSomeNonArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: [["test","val"],"stuff"]');
        $c = new QPeek();
        $c->parse([['test', 'val'], 'stuff']);
    }

    public function testParseInvalidArrayElementsNon0()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: [{"1":"test","2":"stuff"}]');
        $c = new QPeek();
        $c->parse([[1=>'test', 2=>'stuff']]);
    }

    public function testParseInvalidArrayElementsNon1()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QPeek got: [{"0":"test","2":"stuff"}]');
        $c = new QPeek();
        $c->parse([[0=>'test', 2=>'stuff']]);
    }

    public function testParse()
    {
        $c = new QPeek();
        $result = $c->parse([['test', 'stuff']]);
        $this->assertSame([
            [
                'id' => 'test',
                'body' => 'stuff'
            ]
        ], $result);
    }

    public function testParseMoreElements()
    {
        $c = new QPeek();
        $result = $c->parse([['test', 'stuff'], ['test2', 'stuff2']]);
        $this->assertSame([
            [
                'id' => 'test',
                'body' => 'stuff'
            ],
            [
                'id' => 'test2',
                'body' => 'stuff2'
            ]
        ], $result);
    }

}