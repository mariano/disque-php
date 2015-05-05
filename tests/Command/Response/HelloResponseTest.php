<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\HelloResponse;
use Disque\Exception\InvalidCommandResponseException;

class HelloResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new HelloResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyNotEnoughElements()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test","stuff"]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test','stuff']);
    }

    public function testInvalidBodyNotEnoughElementsInNode()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["id","host","port"]]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['version', 'id', ['id', 'host', 'port']]);
    }

    public function testInvalidBodyTooManyElementsInNode()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["id","host","port","version","stuff"]]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['version', 'id', ['id', 'host', 'port', 'version', 'stuff']]);
    }

    public function testParseOneNode()
    {
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['version', 'id', ['id', 'host', 'port', 'version']]);
        $result = $r->parse();
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
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody([
            'version',
            'id',
            ['id', 'host', 'port', 'version'],
            ['id2', 'host2', 'port2', 'version2'],
        ]);
        $result = $r->parse();
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