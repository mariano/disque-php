<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Argument\InvalidCommandArgumentException;
use Disque\Command\CommandInterface;
use Disque\Command\DelJob;
use Disque\Command\Response\InvalidResponseException;

class DelJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new DelJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testGetCommand()
    {
        $c = new DelJob();
        $result = $c->getCommand();
        $this->assertSame('DELJOB', $result);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: []');
        $c = new DelJob();
        $c->setArguments([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [["test","stuff"]]');
        $c = new DelJob();
        $c->setArguments([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [128]');
        $c = new DelJob();
        $c->setArguments([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [""]');
        $c = new DelJob();
        $c->setArguments([""]);
    }

    public function testBuild()
    {
        $c = new DelJob();
        $c->setArguments(['id']);
        $result = $c->getArguments();
        $this->assertSame(['id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new DelJob();
        $c->setArguments(['id', 'id2']);
        $result = $c->getArguments();
        $this->assertSame(['id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\DelJob got: ["test"]');
        $c = new DelJob();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\DelJob got: "test"');
        $c = new DelJob();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new DelJob();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}