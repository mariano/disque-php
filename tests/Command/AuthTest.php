<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\Auth;
use Disque\Command\Response\InvalidResponseException;

class AuthTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new Auth();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new Auth();
        $result = $c->getCommand();
        $this->assertSame('AUTH', $result);
    }

    public function testIsBlocking()
    {
        $c = new Auth();
        $result = $c->isBlocking();
        $this->assertFalse($result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Auth: []');
        $c = new Auth();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Auth: ["test","stuff"]');
        $c = new Auth();
        $c->setArguments(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Auth: {"test":"stuff"}');
        $c = new Auth();
        $c->setArguments(['test' => 'stuff']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Auth: {"1":"stuff"}');
        $c = new Auth();
        $c->setArguments([1 => 'stuff']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\Auth: [false]');
        $c = new Auth();
        $c->setArguments([false]);
    }

    public function testBuild()
    {
        $c = new Auth();
        $c->setArguments(['test']);
        $result = $c->getArguments();
        $this->assertSame(['test'], $result);
    }

    public function testParseInvalidNonString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Auth got: 10');
        $c = new Auth();
        $c->parse(10);
    }

    public function testParseInvalidNonStringBoolTrue()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Auth got: true');
        $c = new Auth();
        $c->parse(true);
    }

    public function testParse()
    {
        $c = new Auth();
        $result = $c->parse('OK');
        $this->assertSame('OK', $result);
    }
}