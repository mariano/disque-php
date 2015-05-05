<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\JobsResponse;
use Disque\Exception\InvalidCommandResponseException;

class JobsResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new JobsResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyNotEnoughElements()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: []');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([]);
    }

    public function testInvalidBodyElementsNotArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyNotEnoughElementsInJob()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["test"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['test']]);
    }

    public function testInvalidBodyTooManyElementsInJob()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["id","body","test"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id', 'body', 'test']]);
    }

    public function testParseOneJob()
    {
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id', 'body']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'id' => 'id',
                'body' => 'body'
            ]
        ], $result);
    }

    public function testParseTwoJobs()
    {
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id', 'body'], ['id2', 'body2']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'id' => 'id',
                'body' => 'body'
            ],
            [
                'id' => 'id2',
                'body' => 'body2'
            ],
        ], $result);
    }
}