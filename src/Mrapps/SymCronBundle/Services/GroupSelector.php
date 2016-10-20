<?php

namespace Mrapps\SymCronBundle\Services;


use Mrapps\SymCronBundle\Entity\GroupTask;

class GroupSelector
{
    private $groups = [];

    public function getNextCandidate()
    {
        /** @var GroupTask $candidate */
        $candidate = $this->getWaitingCandidate();
        

        if ($candidate) {
            return $candidate;
        }

        /** @var GroupTask $group */
        foreach ($this->groups as $group) {

            if (!$candidate || $group->getWeight() < $candidate->getWeight()) {
                if ($group->canStart()) {
                    $candidate = $group;
                }
            }
        }

        return $candidate;
    }

    public function getWaitingCandidate()
    {
        /** @var GroupTask $candidate */
        $candidate = null;

        /** @var GroupTask $group */
        foreach ($this->groups as $group) {

            if (!$candidate || $group->getWeight() < $candidate->getWeight()) {
                if ($group->isWaiting()) {
                    $candidate = $group;
                }
            }
        }

        return $candidate;
    }

    public function addGroups(array $groups)
    {
        $this->groups = $groups;
    }

    public function getAllGroups()
    {
        return $this->groups;
    }

    public function areAllGroupsCompleted()
    {
        /** @var GroupTask $group */
        foreach ($this->groups as $group) {
            if (!$group->isCompleted()) {
                return false;
            }
        }

        return true;
    }
}