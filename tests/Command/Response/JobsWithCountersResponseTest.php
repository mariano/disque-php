<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\Hello;
use Disque\Command\Response\ResponseInterface;
use Disque\Command\Response\JobsWithCountersResponse;
use Disque\Command\Response\InvalidResponseException;
use Disque\Command\Response\JobsResponse AS Response;
use Disque\Command\Response\JobsWithQueueResponse AS Queue;
use Disque\Command\Response\JobsWithCountersResponse AS Counters;


class JobsWithCountersResponseTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $r = new JobsWithCountersResponse();
        $this->assertInstanceOf(ResponseInterface::class, $r);
    }
    
    public function testInvalidBodyNotArrayString()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: "test"');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody('test');
    }
    
    public function testInvalidBodyNotArrayNumeric()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: 128');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody(128);
    }
    
    public function testInvalidBodyNotEnoughElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["id","body"]]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([['id','body']]);
    }
    
    public function testInvalidBodyTooManyElementsInJob()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","id","body","test"]]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'id', 'body', 'test']]);
    }
    
    public function testParseInvalidArrayElementsNon0()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"1":"test","2":"stuff","3":"more"}]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([[1=>'test', 2=>'stuff', 3=>'more']]);
    }
    
    public function testParseInvalidArrayElementsNon1()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"0":"test","2":"stuff","3":"more"}]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([[0=>'test', 2=>'stuff', 3=>'more']]);
    }
    
    public function testParseInvalidArrayElementsNon2()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [{"0":"test","1":"stuff","3":"more"}]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([[0=>'test', 1=>'stuff', 3=>'more']]);
    }
    
    public function testInvalidBodyInvalidJobIDPrefix()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","XX01234567890","body"]]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'XX01234567890', 'body']]);
    }
    
    public function testInvalidBodyInvalidJobIDLength()
    {
        $this->setExpectedException(InvalidResponseException::class, 'Invalid command response. Command Disque\\Command\\Hello got: [["queue","DI012345","body"]]');
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody([['queue', 'DI012345', 'body']]);
    }
    
    public function testParseNoJob()
    {
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());
        $r->setBody(null);
        $result = $r->parse();
        $this->assertSame([], $result);
    }
    
    public function testParseOneJob()
    {
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());

        $queue = 'q';
        $id = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body = 'lorem ipsum';
        $nacks = 1;
        $ad = 2;
        $response = [$queue, $id, $body, 'nacks', $nacks, 'additional-deliveries', $ad];
        $r->setBody([$response]);
        $parsedResponse = $r->parse();

        $this->assertSame([
            [
                Queue::KEY_QUEUE => $queue,
                Response::KEY_ID  => $id,
                Response::KEY_BODY => $body,
                Counters::KEY_NACKS => $nacks,
                Counters::KEY_ADDITIONAL_DELIVERIES => $ad
            ]
        ], $parsedResponse);
    }
    
    public function testParseTwoJobs()
    {
        $r = new JobsWithCountersResponse();
        $r->setCommand(new Hello());

        $queue1 = 'q1';
        $id1 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body1 = 'lorem ipsum';
        $nacks1 = 1;
        $ad1 = 2;

        $queue2 = 'q2';
        $id2 = 'DI0f0c644fd3ccb51c2cedbd47fcb6f312646c993c05a0SQ';
        $body2 = 'dolor sit amet';
        $nacks2 = 3;
        $ad2 = 4;

        $response = [
            [$queue1, $id1, $body1, 'nacks', $nacks1, 'additional-deliveries', $ad1],
            [$queue2, $id2, $body2, 'nacks', $nacks2, 'additional-deliveries', $ad2],
        ];
        $r->setBody($response);
        $parsedResponse = $r->parse();

        $this->assertSame([
            [
                Queue::KEY_QUEUE => $queue1,
                Response::KEY_ID  => $id1,
                Response::KEY_BODY => $body1,
                Counters::KEY_NACKS => $nacks1,
                Counters::KEY_ADDITIONAL_DELIVERIES => $ad1
            ],
            [
                Queue::KEY_QUEUE => $queue2,
                Response::KEY_ID  => $id2,
                Response::KEY_BODY => $body2,
                Counters::KEY_NACKS => $nacks2,
                Counters::KEY_ADDITIONAL_DELIVERIES => $ad2
            ]

        ], $parsedResponse);
    }
}
