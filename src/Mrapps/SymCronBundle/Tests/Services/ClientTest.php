<?php

namespace Mrapps\SymCronBundle\Tests;

use Mrapps\SymCronBundle\Services\Client;
use Mrapps\SymCronBundle\Entity\Task;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends TestCase
{
    public function setUp()
    {
        $this->guzzle = $this->getMockBuilder("Guzzle\\Http\\Client")
            ->setMethods([
                "get"
            ])
            ->getMock();

        $this->response = $this
            ->getMockBuilder("Psr\\Http\\Message\\ResponseInterface")
            ->setMethods([
                'getStatusCode',
            ])
            ->getMock();

        $this->logger = $this->getMockBuilder("Monolog\\Logger")
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testRunTaskReturnsTask()
    {
        $this->guzzle->expects($this->once())
            ->method("get")
            ->willReturn($this->response);

        $client = new Client($this->guzzle, $this->logger, "http://localhost");
        $task = new Task();
        $result = $client->runTask($task);

        $this->assertSame($task, $result);
    }

    public function testTaskIsCompletedWhenResponseIsOk()
    {
        $this->response->expects($this->once())
            ->method("getStatusCode")
            ->willReturn(200);

        $this->guzzle->expects($this->once())
            ->method("get")
            ->willReturn($this->response);

        $client = new Client($this->guzzle, $this->logger, "http://localhost");
        $task = new Task();
        $result = $client->runTask($task);

        $this->assertSame(true, $result->isSuccess());
        $this->assertSame(true, $result->isCompleted());
    }

    public function testTaskIsNotCompletedWhenResponseIsNotOk()
    {
        $this->response->expects($this->exactly(2))
            ->method("getStatusCode")
            ->willReturn(500);

        $this->guzzle->expects($this->once())
            ->method("get")
            ->willReturn($this->response);

        $client = new Client($this->guzzle, $this->logger, "http://localhost");
        $task = new Task();

        $result = $client->runTask($task);

        $this->assertSame(false, $result->isSuccess());
        $this->assertSame(true, $result->isCompleted());
    }


    public function testTaskIsNotStartedAfterStatusCode201()
    {
        $this->response->expects($this->exactly(2))
            ->method("getStatusCode")
            ->willReturn(201);

        $this->guzzle->expects($this->once())
            ->method("get")
            ->willReturn($this->response);

        $client = new Client($this->guzzle, $this->logger, "http://localhost");
        $task = new Task();

        $result = $client->runTask($task);

        $this->assertSame(false, $result->isStarted());
    }
}
