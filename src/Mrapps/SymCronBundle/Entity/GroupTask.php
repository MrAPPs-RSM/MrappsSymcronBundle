<?php

namespace Mrapps\SymCronBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * GroupTask
 *
 * @ORM\Table(name="symcron_group_task")
 * @ORM\Entity(repositoryClass="Mrapps\SymCronBundle\Repository\GroupTaskRepository")
 */
class GroupTask
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer")
     */
    private $weight;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @ORM\OneToMany(targetEntity="Task",  mappedBy="group")
     */
    private $tasks;

    /**
     * @var datetime
     *
     * @ORM\Column(name="startable_after", type="datetime", nullable=true)
     */
    private $startableAfter;

    /**
     * @ORM\ManyToOne(targetEntity="Mrapps\SymCronBundle\Entity\GroupTask")
     * @ORM\JoinColumn(name="parent_group_id", referencedColumnName="id")
     */
    private $parentGroup;

    /**
     * @var int
     *
     * @ORM\Column(name="iterations_limit", type="integer")
     */
    private $iterationsLimit;

    /**
     * @var int
     *
     * @ORM\Column(name="iterations_counter", type="integer")
     */
    private $iterationsCounter;


    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->enabled = true;
        $this->iterationsLimit = 0;
        $this->iterationsCounter = 0;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     *
     * @return GroupTask
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    public function addTask(Task $task)
    {
        $this->tasks->add($task);
    }

    public function getTasks()
    {
        return $this->tasks;
    }

    public function setStartAfter(\DateTime $startableAfter)
    {
        $this->startableAfter = $startableAfter;
    }

    /**
     * Set startableAfter
     *
     * @param \DateTime $startableAfter
     *
     * @return GroupTask
     */
    public function setStartableAfter($startableAfter)
    {
        $this->startableAfter = $startableAfter;

        return $this;
    }

    /**
     * Get startableAfter
     *
     * @return \DateTime
     */
    public function getStartableAfter()
    {
        return $this->startableAfter;
    }

    /**
     * Remove task
     *
     * @param Task $task
     */
    public function removeTask(Task $task)
    {
        $this->tasks->removeElement($task);
    }

    /**
     * Set parentGroup
     *
     * @param GroupTask $parentGroup
     *
     * @return GroupTask
     */
    public function setParentGroup(GroupTask $parentGroup = null)
    {
        $this->parentGroup = $parentGroup;

        return $this;
    }

    /**
     * Get parentGroup
     *
     * @return GroupTask
     */
    public function getParentGroup()
    {
        return $this->parentGroup;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setIterationsCounter($iterationCounter)
    {
        $this->iterationsCounter = $iterationCounter;

        return $this;
    }

    public function setIterationsLimit($iterationLimit)
    {
        $this->iterationsLimit = $iterationLimit;

        return $this;
    }


    public function isStarted()
    {
        foreach ($this->tasks as $task) {
            if ($task->isStarted()) {
                return true;
            }
        }

        return false;
    }

    public function isCompleted()
    {
        /**@var Task $task */
        foreach ($this->tasks as $task) {
            if (!$task->isSuccess()) {
                return false;
            }
        }

        return true;
    }

    public function isWaiting()
    {
        if ($this->isStarted()) {
            /**@var Task $task */
            foreach ($this->tasks as $task) {
                if (((!$task->isStarted()
                        && !$task->isCompleted())
                    || $task->isFailed())
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return GroupTask */
    public function parentGroup()
    {
        return $this->parentGroup;
    }

    public function canStart()
    {
        if ($this->parentGroup()
            && !$this->parentGroup()->isCompleted()
        ) {
            return false;
        }

        if ($this->iterationsLimit > 0
            && $this->iterationsCounter >= $this->iterationsLimit
        ) {
            return false;
        }

        $notStarted = !$this->isStarted();

        if ($notStarted && $this->startableAfter) {

            $now = new \DateTime();

            $nowTime = intval($now->format("Hi"));
            $startableAfterTime = intval($this->startableAfter->format("Hi"));

            return $startableAfterTime < $nowTime && $this->enabled;
        }

        return $notStarted && $this->enabled;
    }

    public function incrementIterationCounter()
    {
        $this->iterationsCounter++;
    }

    public function startedAt()
    {
        /**@var Task $firstTask */
        $firstTask = $this->tasks->get(0);

        return $firstTask->getStartDateTime();
    }

    public function canReiterate()
    {
        if ($this->iterationsLimit == 0
            || $this->iterationsCounter < $this->iterationsLimit
        ) {
            return true;
        }

        $midnight = new \DateTime();
        $midnight->setTime(0, 0, 0);

        return $this->startedAt() < $midnight;
    }
}
