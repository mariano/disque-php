<?php
namespace Disque\Test\Command\Response;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\KeyValueResponse;
use Disque\Command\Response\InvalidResponseException;

class KeyValueResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new KeyValueResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }

    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }

    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }

    public function testInvalidBodyNotEnoughElements()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: []');
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody([]);
    }

    public function testInvalidBodyOddElements()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test"]');
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test']);
    }

    public function testInvalidBodyOddMoreElements()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: ["test","stuff","element"]');
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody(['test', 'stuff', 'element']);
    }

    public function testParseFalse()
    {
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody(false);
        $result = $r->parse();
        $this->assertNull($result);
    }

    public function testParse()
    {
        $r = new KeyValueResponse();
        $r->setCommand(new Hello());
        $r->setBody(['k1', 'v1', 'k2', 'v2']);
        $result = $r->parse();
        $this->assertSame([
            'k1' => 'v1',
            'k2' => 'v2'
        ], $result);
    }
}