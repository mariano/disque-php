<?php
namespace Disque\Test\Command\Response;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\StringResponse;
use Disque\Command\Response\InvalidResponseException;

class StringResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new StringResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotStringArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new StringResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyNotStringNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new StringResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testParse()
    {
        $r = new StringResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
        $result = $r->parse();
        $this->assertSame('test', $result);
    }
}