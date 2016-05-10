<?php
namespace Disque\Test\Command\Response;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\QscanResponse;
use Disque\Command\Response\InvalidResponseException;

class QscanResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new QscanResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyElementsNotEnough()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["10"]');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['10']);
    }

    public function testInvalidBodyElementsTooMany()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["10",["queue1"],"test"]');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['10', ['queue1'], 'test']);
    }

    public function testInvalidBodyElements0NotSet()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: {"9":"10","1":["queue1"]}');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody([9=>'10', 1=>['queue1']]);
    }

    public function testInvalidBodyElements1NotSet()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: {"0":"10","2":["queue1"]}');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody([0=>'10', 2=>['queue1']]);
    }

    public function testInvalidBodyElement1NotNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test",["queue1"]]');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test', ['queue1']]);
    }

    public function testInvalidBodyElement2NotArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test","queue1"]');
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test', 'queue1']);
    }

    public function testParseNoValues()
    {
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['0', []]);
        $result = $r->parse();
        $this->assertSame([
            'finished' => true,
            'nextCursor' => 0,
            'queues' => [
            ]
        ], $result);
    }

    public function testParseOneValue()
    {
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['0', ['queue1']]);
        $result = $r->parse();
        $this->assertSame([
            'finished' => true,
            'nextCursor' => 0,
            'queues' => [
                'queue1'
            ]
        ], $result);
    }

    public function testParseSeveralValues()
    {
        $r = new QscanResponse();
        $r->setCommand(new Hello());
        $r->setBody(['1', ['queue1', 'queue2']]);
        $result = $r->parse();
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