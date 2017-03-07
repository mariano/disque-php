<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\QStat;
use Disque\Command\Response\InvalidResponseException;

class QStatTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new QStat();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new QStat();
        $result = $c->getCommand();
        $this->assertSame('QSTAT', $result);
    }

    public function testIsBlocking()
    {
        $c = new QStat();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QStat: []');
        $c = new QStat();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QStat: ["test","stuff"]');
        $c = new QStat();
        $c->setArguments(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QStat: {"test":"stuff"}');
        $c = new QStat();
        $c->setArguments(['test' => 'stuff']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QStat: {"1":"stuff"}');
        $c = new QStat();
        $c->setArguments([1 => 'stuff']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QStat: [false]');
        $c = new QStat();
        $c->setArguments([false]);
    }

    public function testBuild()
    {
        $c = new QStat();
        $c->setArguments(['test']);
        $result = $c->getArguments();
        $this->assertSame(['test'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\QStat got: ["test"]');
        $c = new QStat();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\QStat got: "test"');
        $c = new QStat();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new QStat();
        $result = $c->parse(['name', 'test', 'len', 1]);
        $this->assertSame([
            'name' => 'test',
            'len' => 1
        ], $result);
    }
}
