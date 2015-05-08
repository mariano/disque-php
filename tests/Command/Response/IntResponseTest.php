<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\IntResponse;
use Disque\Command\Response\InvalidResponseException;

class IntResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new IntResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new IntResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new IntResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testParse()
    {
        $r = new IntResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
        $result = $r->parse();
        $this->assertSame(128, $result);
    }
}