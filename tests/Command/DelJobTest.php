<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\DelJob;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class DelJobTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new DelJob();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: []');
        $c = new DelJob();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsNonStringArray()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [["test","stuff"]]');
        $c = new DelJob();
        $c->build([['test','stuff']]);
    }

    public function testBuildInvalidArgumentsNonStringNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [128]');
        $c = new DelJob();
        $c->build([128]);
    }

    public function testBuildInvalidArgumentsEmptyValue()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\DelJob: [""]');
        $c = new DelJob();
        $c->build([""]);
    }

    public function testBuild()
    {
        $c = new DelJob();
        $result = $c->build(['id']);
        $this->assertSame(['DELJOB', 'id'], $result);
    }

    public function testBuildSeveral()
    {
        $c = new DelJob();
        $result = $c->build(['id', 'id2']);
        $this->assertSame(['DELJOB', 'id', 'id2'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\DelJob got: ["test"]');
        $c = new DelJob();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\DelJob got: "test"');
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