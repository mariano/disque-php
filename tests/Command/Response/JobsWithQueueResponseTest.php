<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\JobsWithQueueResponse;
use Disque\Exception\InvalidCommandResponseException;

class JobsWithQueueResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new JobsWithQueueResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyNotEnoughElements()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: []');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([]);
    }

    public function testInvalidBodyElementsNotArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyNotEnoughElementsInJob()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["id","body"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id','body']]);
    }

    public function testInvalidBodyTooManyElementsInJob()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","id","body","test"]]');
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'id', 'body', 'test']]);
    }

    public function testParseOneJob()
    {
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'id', 'body']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'id',
                'body' => 'body'
            ]
        ], $result);
    }

    public function testParseTwoJobs()
    {
        $r = new JobsWithQueueResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'id', 'body'], ['queue2', 'id2', 'body2']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'queue' => 'queue',
                'id' => 'id',
                'body' => 'body'
            ],
            [
                'queue' => 'queue2',
                'id' => 'id2',
                'body' => 'body2'
            ],
        ], $result);
    }
}