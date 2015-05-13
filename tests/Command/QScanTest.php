<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\QScan;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\Argument\InvalidOptionException;
use Disque\Command\CommandInterface;
use Disque\Command\Response\InvalidResponseException;

class QScanTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new QScan();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new QScan();
        $result = $c->getCommand();
        $this->assertSame('QSCAN', $result);
    }

    public function testBuildInvalidArgumentTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QScan: [0,{"count":10},["stuff"]]');
        $c = new QScan();
        $c->setArguments([0, ['count' => 10], ['stuff']]);
    }

    public function testBuildInvalidArgument0NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QScan: {"2":0,"1":{"count":10}}');
        $c = new QScan();
        $c->setArguments([2=>0, 1=>['count' => 10]]);
    }

    public function testBuildInvalidCursorNotNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QScan: ["test"]');
        $c = new QScan();
        $c->setArguments(['test']);
    }

    public function testBuildInvalidArgument1NotSet()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QScan: {"0":0,"2":{"count":10}}');
        $c = new QScan();
        $c->setArguments([0=>0, 2=>['count' => 10]]);
    }

    public function testBuildInvalidArgumentOptionsNonArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QScan: [0,"test"]');
        $c = new QScan();
        $c->setArguments([0, 'test']);
    }

    public function testBuildInvalidOption()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"test":"stuff"}');
        $c = new QScan();
        $c->setArguments([0, ['test' => 'stuff']]);
    }

    public function testBuildInvalidOptionWithValid()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"test":"stuff","count":10}');
        $c = new QScan();
        $c->setArguments([0, ['test' => 'stuff', 'count' => 10]]);
    }

    public function testBuildInvalidOptionMinlenNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"minlen":"stuff"}');
        $c = new QScan();
        $c->setArguments([0, ['minlen' => 'stuff']]);
    }

    public function testBuildInvalidOptionMinlenNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"minlen":3.14}');
        $c = new QScan();
        $c->setArguments([0, ['minlen' => 3.14]]);
    }

    public function testBuildInvalidOptionMaxlenNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"maxlen":"stuff"}');
        $c = new QScan();
        $c->setArguments([0, ['maxlen' => 'stuff']]);
    }

    public function testBuildInvalidOptionMaxlenNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"maxlen":3.14}');
        $c = new QScan();
        $c->setArguments([0, ['maxlen' => 3.14]]);
    }

    public function testBuildInvalidOptionImportrateNonNumeric()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"importrate":"stuff"}');
        $c = new QScan();
        $c->setArguments([0, ['importrate' => 'stuff']]);
    }

    public function testBuildInvalidOptionImportrateNonInt()
    {
        $this->setExpectedException(InvalidOptionException::class, 'Invalid command options. Options for command Disque\\Command\\QScan: {"importrate":3.14}');
        $c = new QScan();
        $c->setArguments([0, ['importrate' => 3.14]]);
    }

    public function testBuildNoArguments()
    {
        $c = new QScan();
        $c->setArguments([]);
        $result = $c->getArguments();
        $this->assertSame([0], $result);
    }

    public function testBuildOptionCount()
    {
        $c = new QScan();
        $c->setArguments([0, ['count' => 10]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'COUNT', 10], $result);
    }

    public function testBuildOptionMinlen()
    {
        $c = new QScan();
        $c->setArguments([0, ['minlen' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'MINLEN', 3000], $result);
    }

    public function testBuildOptionBusyloop()
    {
        $c = new QScan();
        $c->setArguments([0, ['busyloop' => true]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'BUSYLOOP'], $result);
    }

    public function testBuildOptionBusyloopAndMinlen()
    {
        $c = new QScan();
        $c->setArguments([0, ['busyloop' => true, 'minlen' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'BUSYLOOP', 'MINLEN', 3000], $result);
    }

    public function testBuildOptionMaxlen()
    {
        $c = new QScan();
        $c->setArguments([0, ['maxlen' => 5]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'MAXLEN', 5], $result);
    }

    public function testBuildOptionImportrate()
    {
        $c = new QScan();
        $c->setArguments([0, ['importrate' => 3000]]);
        $result = $c->getArguments();
        $this->assertSame([0, 'IMPORTRATE', 3000], $result);
    }

    public function testBuildCursor()
    {
        $c = new QScan();
        $c->setArguments([1]);
        $result = $c->getArguments();
        $this->assertSame([1], $result);
    }

    public function testBuildCursorAndOptions()
    {
        $c = new QScan();
        $c->setArguments([1, ['count' => 10, 'maxlen' => 3]]);
        $result = $c->getArguments();
        $this->assertSame([1, 'COUNT', 10, 'MAXLEN', 3], $result);
    }

    public function testParseInvalidNonArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\QScan got: 10');
        $c = new QScan();
        $c->parse(10);
    }

    public function testParseNoCursor()
    {
        $c = new QScan();
        $result = $c->parse(['1', ['queue1', 'queue2']]);
        $this->assertSame([
            'finished' => false,
            'nextCursor' => 1,
            'queues' => [
                'queue1',
                'queue2'
            ]
        ], $result);
    }
}