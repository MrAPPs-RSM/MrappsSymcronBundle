<?php

namespace Mrapps\SymCronBundle\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Mrapps\SymCronBundle\Services\CronManager;
use PHPUnit\Framework\TestCase;

class CronManagerTest extends TestCase
{
    public function setUp()
    {
        $this->selector = $this->getMockBuilder("Mrapps\\SymCronBundle\\Services\\GroupSelector")
            ->getMock();

        $this->groupTask = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\GroupTask")
            ->getMock();

        $this->entityManager = $this->getMockBuilder("Doctrine\\ORM\\EntityManager")
            ->disableOriginalConstructor()
            ->getMock();

        $this->emptyTasksCollection = new ArrayCollection();
    }

    public function testWhenNoTaskToRun()
    {
        $this->groupTask->expects($this->once())
            ->method("getTasks")
            ->willReturn($this->emptyTasksCollection);

        $this->selector->expects($this->once())
            ->method("getNextCandidate")
            ->willReturn($this->groupTask);

        $this->manager = new CronManager($this->selector, $this->entityManager);
        $task = $this->manager->nextActivity();
        $this->assertNull($task);
    }

    public function testWhenTaskWithLowerWeightAvailable()
    {
        $taskWeight1CanStart = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight1CanStart->expects($this->once())
            ->method("getWeight")
            ->willReturn(1);

        $taskWeight1CanStart->expects($this->once())
            ->method("canStart")
            ->willReturn(true);

        $taskWeight2 = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight2->expects($this->once())
            ->method("getWeight")
            ->willReturn(2);

        $tasksCollection = new ArrayCollection();
        $tasksCollection->add($taskWeight1CanStart);
        $tasksCollection->add($taskWeight2);

        $this->groupTask->expects($this->once())
            ->method("getTasks")
            ->willReturn($tasksCollection);

        $this->selector->expects($this->once())
            ->method("getNextCandidate")
            ->willReturn($this->groupTask);

        $this->manager = new CronManager($this->selector, $this->entityManager);

        $task = $this->manager->nextActivity();

        $this->assertSame($taskWeight1CanStart, $task);
    }

    public function testWhenTaskWithLowerWeightInProgressSoNoTaskToRun()
    {
        $taskWeight1Successful = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight1Successful->expects($this->once())
            ->method("isStarted")
            ->willReturn(true);

        $taskWeight1Successful->expects($this->once())
            ->method("isCompleted")
            ->willReturn(false);

        $taskWeight1Successful->expects($this->never())
            ->method("canStart");

        $taskWeight2CanStart = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight2CanStart->expects($this->never())
            ->method("getWeight");

        $taskWeight1Successful->expects($this->never())
            ->method("canStart");

        $tasksCollection = new ArrayCollection();
        $tasksCollection->add($taskWeight1Successful);
        $tasksCollection->add($taskWeight2CanStart);

        $this->groupTask->expects($this->once())
            ->method("getTasks")
            ->willReturn($tasksCollection);

        $this->selector->expects($this->once())
            ->method("getNextCandidate")
            ->willReturn($this->groupTask);

        $this->manager = new CronManager($this->selector, $this->entityManager);

        $task = $this->manager->nextActivity();

        $this->assertNull($task);
    }

    public function testWhenNoGroupCandidateAvailable()
    {
        $this->selector->expects($this->once())
            ->method("getNextCandidate")
            ->willReturn(null);

        $this->manager = new CronManager($this->selector, $this->entityManager);
        $task = $this->manager->nextActivity();
        $this->assertNull($task);
    }

    public function testWhenNoGroupCandidateAvailableTryToRestartGroupCicle()
    {
        $this->selector->expects($this->once())
            ->method("areAllGroupsCompleted")
            ->willReturn(true);

        $this->manager = new CronManager($this->selector, $this->entityManager);
        $shouldRestart = $this->manager->shouldRestart();
        $this->assertSame(true, $shouldRestart);
    }

    public function testResetWhenShouldRestart()
    {
        $this->selector->expects($this->exactly(2))
            ->method("getNextCandidate")
            ->will($this->onConsecutiveCalls(null, $this->groupTask));

        $this->selector->expects($this->exactly(1))
            ->method("areAllGroupsCompleted")
            ->will($this->onConsecutiveCalls(true));

        $groups = [$this->groupTask];
        $this->selector->expects($this->once())
            ->method("getAllGroups")
            ->willReturn($groups);

        $taskWeight1CanStart = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight1CanStart->expects($this->once())
            ->method("getWeight")
            ->willReturn(1);

        $taskWeight1CanStart->expects($this->once())
            ->method("canStart")
            ->willReturn(true);

        $taskWeight2 = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\Task")
            ->getMock();

        $taskWeight2->expects($this->once())
            ->method("getWeight")
            ->willReturn(2);

        $tasksCollection = new ArrayCollection();
        $tasksCollection->add($taskWeight1CanStart);
        $tasksCollection->add($taskWeight2);

        $this->groupTask->expects($this->any())
            ->method("getTasks")
            ->willReturn($tasksCollection);


        $this->manager = new CronManager($this->selector, $this->entityManager);
        $task = $this->manager->nextActivity();
        $this->assertSame($taskWeight1CanStart, $task);
    }
}