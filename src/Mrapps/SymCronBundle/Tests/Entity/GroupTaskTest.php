<?php

namespace Mrapps\SymCronBundle\Tests;

use Mrapps\SymCronBundle\Entity\GroupTask;
use Mrapps\SymCronBundle\Entity\Task;
use PHPUnit\Framework\TestCase;

class GroupTaskTest extends TestCase
{
    public function setUp()
    {
        $this->group = new GroupTask();

        $this->startedTask = new Task();
        $this->startedTask->setStarted();

        $this->completedTask = new Task();
        $this->completedTask->setCompleted();
        $this->completedTask->setSuccess(true);
    }

    public function testGroupTaskNotInProgressWhenCreated()
    {
        $this->assertSame(false, $this->group->isStarted());
    }

    public function testGroupCanStartWhenNotStarted()
    {
        $this->assertSame(true, $this->group->canStart());
    }

    public function testGroupCantStartBeforeStarterTime()
    {
        $nowLess3h = new \DateTime("-3 h");
        $this->group->setStartAfter($nowLess3h);

        $this->assertSame(false, $this->group->canStart());
    }

    public function testIsStartedWhenAtLeastOneOwnedTaskIsStarted()
    {
        $this->group->addTask($this->startedTask);
        $this->assertSame(true, $this->group->isStarted());
    }

    public function testIsCompleteWhenAllTasksAreCompleted()
    {
        $this->group->addTask($this->completedTask);
        $this->assertSame(true, $this->group->isCompleted());
    }

    public function testGroupCantStartWhenParentNotCompleted()
    {
        $parentGroup = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\GroupTask")
            ->getMock();

        $parentGroup->expects($this->once())
            ->method("isCompleted")
            ->willReturn(false);

        $this->group->setParentGroup($parentGroup);

        $this->assertSame(false, $this->group->canStart());
    }

    public function testGroupWaitingWithTaskToRun()
    {
        $successfulTask=new Task();
        $successfulTask->setStarted();
        $successfulTask->setCompleted();
        $successfulTask->setSuccess(true);

        $this->group->addTask($successfulTask);

        $waitingTask=new Task();

        $this->group->addTask($waitingTask);

        $this->assertSame(true, $this->group->isWaiting());
    }

    public function testGroupWaitingWithFailedTaskToRun()
    {
        $failedTask=new Task();
        $failedTask->setStarted();
        $failedTask->setCompleted();
        $failedTask->setSuccess(false);

        $this->group->addTask($failedTask);

        $this->assertSame(true, $this->group->isWaiting());
    }

    public function testGroupWithoutWaitingTask()
    {
        $completedTask=new Task();
        $completedTask->setStarted();
        $completedTask->setCompleted();
        $completedTask->setSuccess(true);

        $this->group->addTask($completedTask);

        $this->assertSame(false, $this->group->isWaiting());
    }

    public function testGroupCantStartIfNotEnabled()
    {
        $this->group->setEnabled(false);
        $this->assertSame(false, $this->group->canStart());
    }

    public function testGroupCantStartIfIterationsLimitPassed()
    {
        $this->group->setIterationsCounter(1);
        $this->group->setIterationsLimit(1);

        $this->assertSame(false, $this->group->canStart());
    }

    public function testGroupCanReiterateWhenCounterUnderLimit()
    {
        $this->group->setIterationsCounter(1);
        $this->group->setIterationsLimit(2);

        $this->assertSame(true, $this->group->canReiterate());
    }

    public function testGroupCanReiterateAfterMidnight()
    {
        $this->group->setIterationsCounter(1);
        $this->group->setIterationsLimit(1);

        $successfulTask=new Task();

        $yesterday=new \DateTime();
        $yesterday->setTime(0,0,0);

        $interval = new \DateInterval("PT15M");
        $interval->invert = 1;
        $yesterday->add($interval);

        $successfulTask->setStartDateTime($yesterday);
        $successfulTask->setCompleted();
        $successfulTask->setSuccess(true);

        $this->group->addTask($successfulTask);

        $this->assertSame(true, $this->group->canReiterate());
    }
}
