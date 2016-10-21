<?php

namespace Mrapps\SymCronBundle\Services;

use Doctrine\ORM\EntityManager;
use Mrapps\SymCronBundle\Entity\GroupTask;
use Mrapps\SymCronBundle\Entity\Task;

class CronManager
{
    private $groupSelector;

    private $entityManager;

    private $cleanup;

    public function __construct(
        GroupSelector $groupSelector,
        EntityManager $entityManager
    ) {
        $this->groupSelector = $groupSelector;
        $this->entityManager = $entityManager;
        $this->cleanup = false;
    }

    public function nextActivity()
    {
        $groupCandidate = $this->groupSelector->getNextCandidate();

        if ($groupCandidate == null) {
            if (
                $this->groupSelector->areAllGroupsCompleted()
                && !$this->cleanup
            ) {
                $midnight = new \DateTime();
                $midnight->setTime(0, 0, 0);

                $groups = $this->groupSelector->getAllGroups();

                /**@var GroupTask $group */
                foreach ($groups as $group) {
                    if ($group->canReiterate()) {
                        if ($group->startedAt() < $midnight) {
                            $group->setIterationsCounter(0);
                        }

                        /**@var Task $task */
                        foreach ($group->getTasks() as $task) {
                            $task->setStartDateTime(null);
                            $task->setEndDateTime(null);
                            $task->setSuccess(false);
                            $this->entityManager->persist($task);
                        }
                    }
                }

                $this->cleanup = true;

                return $this->nextActivity();
            }

            return null;
        }

        /**@var Task $candidate */
        $candidate = null;

        /**@var Task $task */
        foreach ($groupCandidate->getTasks() as $task) {
            if (
                !$candidate ||
                $task->getWeight() < $candidate->getWeight()
            ) {
                if (
                    $task->isStarted()
                    && !$task->isCompleted()
                ) {
                    return null;
                }

                if ($task->canStart()) {
                    $candidate = $task;
                }
            }
        }

        return $candidate;
    }

    public function shouldRestart()
    {
        return $this->groupSelector->areAllGroupsCompleted();
    }
}
