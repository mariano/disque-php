<?php
namespace Disque\Test\Command\Response;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\HelloResponse;
use Disque\Command\Response\InvalidResponseException;

class HelloResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new HelloResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyNotEnoughElements()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test","stuff"]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test','stuff']);
    }

    public function testInvalidBodyNotEnoughElementsInNode()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["id","host","port"]]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['version', 'id', ['id', 'host', 'port']]);
    }

    public function testInvalidBodyTooManyElementsInNode()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["version","id",["id","host","port","version","stuff"]]');
        $r = new HelloResponse();
        $r->setCommand(new Hello());
        $r->setBody(['version', 'id', ['id', 'host', 'port', 'version', 'stuff']]);
    }

    public function testParseOneNode()
    {
        $r = new HelloResponse();
        $r->setCommand(new Hello());

        $r->setBody(['version', 'id', ['id', 'host', 'port', 'priority']]);
        $result = $r->parse();
        $this->assertSame([
            'version' => 'version',
            'id' => 'id',
            'nodes' => [
                [
                    'id' => 'id',
                    'host' => 'host',
                    'port' => 'port',
                    'priority' => 'priority'
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
            ['id', 'host', 'port', '1'],
            ['id2', 'host2', 'port2', '2'],
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
                    'priority' => '1'
                ],
                [
                    'id' => 'id2',
                    'host' => 'host2',
                    'port' => 'port2',
                    'priority' => '2'
                ]
            ]
        ], $result);
    }

}