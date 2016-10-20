<?php

namespace Mrapps\SymCronBundle\Tests;

use Mrapps\SymCronBundle\Services\GroupSelector;
use PHPUnit\Framework\TestCase;

class GroupSelectoTest extends TestCase
{
    public function setUp()
    {
        $this->selector = new GroupSelector();

        $this->group1 = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\GroupTask")
            ->getMock();
        $this->group2 = $this->getMockBuilder("Mrapps\\SymCronBundle\\Entity\\GroupTask")
            ->getMock();
    }

    public function testNoNextCandidateWhenNoGroups()
    {
        $this->assertNull($this->selector->getNextCandidate());
    }

    public function testNextCandidateWhenGroupWithLowerWeightAvailable()
    {
        $this->group1->expects($this->once())
            ->method("getWeight")
            ->willReturn(1);

        $this->group1->expects($this->once())
            ->method("canStart")
            ->willReturn(true);

        $this->group2->expects($this->once())
            ->method("getWeight")
            ->willReturn(2);

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame($this->group1, $this->selector->getNextCandidate());
    }

    public function testNextCandidateWhenGroupWithLowerWeightAndNotStarted()
    {
        $this->group1->expects($this->never())
            ->method("getWeight");

        $this->group1->expects($this->once())
            ->method("canStart")
            ->willReturn(false);

        $this->group2->expects($this->never())
            ->method("getWeight");

        $this->group2->expects($this->once())
            ->method("canStart")
            ->willReturn(true);

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame($this->group2, $this->selector->getNextCandidate());
    }

    public function testWaitingCandidateWhenGroupWithLowerWeightAndNotRunningTask()
    {
        $this->group1->expects($this->once())
            ->method("getWeight")
            ->willReturn(1);

        $this->group1->expects($this->once())
            ->method("isWaiting")
            ->willReturn(true);

        $this->group2->expects($this->once())
            ->method("getWeight")
            ->willReturn(2);

        $this->group2->expects($this->never())
            ->method("isWaiting");

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame($this->group1, $this->selector->getWaitingCandidate());
    }

    public function testNoWaitingCandidateWhenNoWaitingTask()
    {
        $this->group1->expects($this->never())
            ->method("getWeight");

        $this->group1->expects($this->once())
            ->method("isWaiting")
            ->willReturn(false);

        $this->group2->expects($this->never())
            ->method("getWeight");

        $this->group2->expects($this->once())
            ->method("isWaiting")
            ->willReturn(false);

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertNull($this->selector->getWaitingCandidate());
    }

    public function testNextCandidateReturnWaitingCandidateWhenAvailable()
    {
        $this->group1->expects($this->never())
            ->method("getWeight");

        $this->group1->expects($this->never())
            ->method("canStart");

        $this->group2->expects($this->once())
            ->method("isWaiting")
            ->willReturn(true);

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame($this->group2, $this->selector->getNextCandidate());
    }

    public function testThatAllGroupsAreCompleted()
    {
        $this->group1->expects($this->once())
            ->method("isCompleted")
            ->willReturn(true);

        $this->group2->expects($this->once())
            ->method("isCompleted")
            ->willReturn(true);

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame(true, $this->selector->areAllGroupsCompleted());
    }


    public function testThatAllGroupsArentCompleted()
    {
        $this->group1->expects($this->once())
            ->method("isCompleted")
            ->willReturn(false);

        $this->group2->expects($this->never())
            ->method("isCompleted");

        $this->selector->addGroups([
            $this->group1,
            $this->group2
        ]);

        $this->assertSame(false, $this->selector->areAllGroupsCompleted());
    }
}