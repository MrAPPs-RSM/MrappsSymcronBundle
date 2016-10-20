<?php

namespace Mrapps\SymCronBundle\Tests;

use Mrapps\SymCronBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function setUp()
    {
        $this->task = new Task();
    }

    public function testTaskNotInProgressWhenCreated()
    {
        $this->assertSame(false, $this->task->isStarted());
    }

    public function testTaskStartedWhenStartDateTimeAvailable()
    {
        $this->task->setStarted();
        $this->assertSame(true, $this->task->isStarted());
    }

    public function testTaskStartedWhenStartDateTimeAvailableAndNoEndDateTime()
    {
        $this->task->setStarted();

        $this->assertSame(false, $this->task->isCompleted());
    }

    public function testTaskNotCompletedWhenEndDateTimeNotAvailable()
    {
        $this->assertSame(false, $this->task->isCompleted());
    }

    public function testTaskCompletedWhenEndDateTimeAvailable()
    {
        $this->task->setCompleted();
        $this->assertSame(true, $this->task->isCompleted());
    }

    public function testTaskCanStartWhenNotStarted()
    {
        $this->assertSame(true, $this->task->canStart());
    }

    public function testTaskCanStartAfterFail()
    {
        $this->task->setStarted();
        $this->task->setCompleted();
        $this->task->setSuccess(false);

        $this->assertSame(true, $this->task->isFailed());
        $this->assertSame(true, $this->task->canStart());
    }

    public function testTaskCanStartAfterIsStartedMoreOrEqualThan1Hour()
    {
        $startedDate=new \DateTime();
        $startedDate->modify("-1 hour");

        $this->task->setStartDateTime($startedDate);
        $this->assertSame(true, $this->task->canStart());
    }

    public function testTaskCantStartAfterIsStarted30minutesAgo()
    {
        $startedDate=new \DateTime();
        $startedDate->modify("-30 minute");

        $this->task->setStartDateTime($startedDate);
        $this->assertSame(false, $this->task->canStart());
    }

    public function testTaskWithValidPOSTOperationReturnTask()
    {
        $returnedTask = $this->task->setMethod("POST");
        $this->assertSame($this->task, $returnedTask);
    }
}
