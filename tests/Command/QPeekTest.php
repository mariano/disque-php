<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\QPeek;
use Disque\Command\Response\InvalidResponseException;

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

    public function testIsBlocking()
    {
        $c = new QPeek();
        $result = $c->isBlocking();
        $this->assertFalse($result);
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

    public function testParse()
    {
        $c = new QPeek();
        $result = $c->parse([['queue', 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'stuff']]);
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'stuff'
            ]
        ], $result);
    }
}