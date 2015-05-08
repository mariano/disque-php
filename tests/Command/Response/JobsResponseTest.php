<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\JobsResponse;
use Disque\Command\Response\InvalidResponseException;

class JobsResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new JobsResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyElementsNotArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyNotEnoughElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["test"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['test']]);
    }

    public function testInvalidBodyTooManyElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["id","body","test"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id', 'body', 'test']]);
    }

    public function testInvalidBodyInvalidJobIDPrefix()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["XX01234567890","body"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['XX01234567890', 'body']]);
    }

    public function testInvalidBodyInvalidJobIDLength()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["DI012345","body"]]');
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['DI012345', 'body']]);
    }

    public function testParseNoJob()
    {
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody(null);
        $result = $r->parse();
        $this->assertSame([], $result);
    }

    public function testParseOneJob()
    {
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([['DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'body']]);
        $result = $r->parse();
        $this->assertSame([
            [
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'body'
            ]
        ], $result);
    }

    public function testParseTwoJobs()
    {
        $r = new JobsResponse();
        $r->setCommand(new Hello());
        $r->setBody([
            ['DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ', 'body'],
            ['DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a1SQ', 'body2']
        ]);
        $result = $r->parse();
        $this->assertSame([
            [
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ',
                'body' => 'body'
            ],
            [
                'id' => 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a1SQ',
                'body' => 'body2'
            ],
        ], $result);
    }
}