<?php
namespace Disque\Test\Command\Response;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\JobsWithQueueResponse;
use Disque\Command\Response\InvalidResponseException;

class JobsWithQueueResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new JobsWithQueueResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyElementsNotArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyNotEnoughElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["id","body"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id','body']]);
    }

    public function testInvalidBodyTooManyElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","id","body","test"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'id', 'body', 'test']]);
    }

    public function testParseInvalidArrayElementsNon0()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"1":"test","2":"stuff","3":"more"}]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([[1=>'test', 2=>'stuff', 3=>'more']]);
    }

    public function testParseInvalidArrayElementsNon1()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"0":"test","2":"stuff","3":"more"}]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([[0=>'test', 2=>'stuff', 3=>'more']]);
    }

    public function testParseInvalidArrayElementsNon2()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"0":"test","1":"stuff","3":"more"}]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([[0=>'test', 1=>'stuff', 3=>'more']]);
    }

    public function testInvalidBodyInvalidJobIDPrefix()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","XX01234567890","body"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'XX01234567890', 'body']]);
    }

    public function testInvalidBodyInvalidJobIDLength()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","D-012345","body"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'D-012345', 'body']]);
    }

    public function testParseNoJob()
    {
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody(null);
        $result = $r->parse();
        $this->assertSame([], $result);
    }

    public function testParseOneJob()
    {
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'body']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'body'
            ]
        ], $result);
    }

    public function testParseTwoJobs()
    {
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([
            ['queue', 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'body'],
            ['queue2', 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a1SQ', 'body2']
        ]);
        $result = $r->parse();
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'body'
            ],
            [
                'queue' => 'queue2',
                'id' => 'D-0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a1SQ',
                'body' => 'body2'
            ],
        ], $result);
    }
}