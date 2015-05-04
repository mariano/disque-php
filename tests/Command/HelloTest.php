<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\Hello;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class HelloTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Hello();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Hello();
        $result = $c->getCommand();
        $this->assertSame('HELLO', $result);
    }

    public function testBuildInvalidArguments()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Hello: ["test"]');
        $c = new Hello();
        $c->setArguments(['test']);
    }

    public function testBuild()
    {
        $c = new Hello();
        $c->setArguments([]);
        $result = $c->getArguments();
        $this->assertSame([], $result);
    }

    public function testParseInvalidNonArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $c = new Hello();
        $c->parse('test');
    }

    public function testParseInvalidEmptyArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: []');
        $c = new Hello();
        $c->parse([]);
    }

    public function testParseInvalidArrayTooShort()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $c = new Hello();
        $c->parse(['test']);
    }

    public function testParseInvalidArray0NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: {"test":"stuff","1":"more","2":"elements"}');
        $c = new Hello();
        $c->parse(['test'=>'stuff',1=>'more',2=>'elements']);
    }

    public function testParseInvalidArray1NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: {"test":"stuff","0":"more","2":"elements"}');
        $c = new Hello();
        $c->parse(['test'=>'stuff',0=>'more',2=>'elements']);
    }

    public function testParseInvalidArray2NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: {"test":"stuff","0":"more","1":"elements"}');
        $c = new Hello();
        $c->parse(['test'=>'stuff',0=>'more',1=>'elements']);
    }

    public function testParseInvalidNodeNotArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id","test"]');
        $c = new Hello();
        $c->parse(['version', 'id', 'test']);
    }

    public function testParseInvalidNodeEmpty()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",[]]');
        $c = new Hello();
        $c->parse(['version', 'id', []]);
    }

    public function testParseInvalidNodeTooShort()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["one","two","three"]]');
        $c = new Hello();
        $c->parse(['version', 'id', ['one', 'two', 'three']]);
    }

    public function testParseInvalidNodeTooLong()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["one","two","three","four","five"]]');
        $c = new Hello();
        $c->parse(['version', 'id', ['one', 'two', 'three', 'four', 'five']]);
    }

    public function testParseInvalidNode0NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",{"1":"one","2":"two","3":"three","4":"four"}]');
        $c = new Hello();
        $c->parse(['version', 'id', [1=>'one', 2=>'two', 3=>'three', 4=>'four']]);
    }

    public function testParseInvalidNode1NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",{"0":"one","2":"two","3":"three","4":"four"}]');
        $c = new Hello();
        $c->parse(['version', 'id', [0=>'one', 2=>'two', 3=>'three', 4=>'four']]);
    }

    public function testParseInvalidNode2NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",{"0":"one","1":"two","3":"three","4":"four"}]');
        $c = new Hello();
        $c->parse(['version', 'id', [0=>'one', 1=>'two', 3=>'three', 4=>'four']]);
    }

    public function testParseInvalidNode3NotSet()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",{"0":"one","1":"two","2":"three","4":"four"}]');
        $c = new Hello();
        $c->parse(['version', 'id', [0=>'one', 1=>'two', 2=>'three', 4=>'four']]);
    }

    public function testParseOneNode()
    {
        $c = new Hello();
        $result = $c->parse(['version', 'id', ['id', 'host', 'port', 'version']]);
        $this->assertSame([
            'version' => 'version',
            'id' => 'id',
            'nodes' => [
                [
                    'id' => 'id',
                    'host' => 'host',
                    'port' => 'port',
                    'version' => 'version'
                ]
            ]
        ], $result);
    }

    public function testParseTwoNodes()
    {
        $c = new Hello();
        $result = $c->parse([
            'version',
            'id',
            ['id', 'host', 'port', 'version'],
            ['id2', 'host2', 'port2', 'version2'],
        ]);
        $this->assertSame([
            'version' => 'version',
            'id' => 'id',
            'nodes' => [
                [
                    'id' => 'id',
                    'host' => 'host',
                    'port' => 'port',
                    'version' => 'version'
                ],
                [
                    'id' => 'id2',
                    'host' => 'host2',
                    'port' => 'port2',
                    'version' => 'version2'
                ]
            ]
        ], $result);
    }
}