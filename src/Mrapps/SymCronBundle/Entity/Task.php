<?php

namespace Mrapps\SymCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Task
 *
 * @ORM\Table(name="symcron_task")
 * @ORM\Entity(repositoryClass="Mrapps\SymCronBundle\Repository\TaskRepository")
 */
class Task
{
    const VALID_METHODS = [
        "GET",
        "POST",
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", type="integer", nullable=false)
     */
    private $weight;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date_time", type="datetime", nullable=true)
     */
    private $startDateTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date_time", type="datetime", nullable=true)
     */
    private $endDateTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="success", type="boolean")
     */
    private $success;

    /**
     * @ORM\ManyToOne(targetEntity="Mrapps\SymCronBundle\Entity\GroupTask", inversedBy="tasks")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    private $group;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=20)
     */
    private $method;


    public function __construct()
    {
        $this->success = false;
        $this->method = "GET";
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
     * Set url
     *
     * @param string $url
     *
     * @return Task
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     *
     * @return Task
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Set startDateTime
     *
     * @param \DateTime $startDateTime
     *
     * @return Task
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    /**
     * Get startDateTime
     *
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set endDateTime
     *
     * @param \DateTime $endDateTime
     *
     * @return Task
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;

        return $this;
    }

    /**
     * Get endDateTime
     *
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Get success
     *
     * @return boolean
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set group
     *
     * @param \Mrapps\SymCronBundle\Entity\GroupTask $group
     *
     * @return Task
     */
    public function setGroup(\Mrapps\SymCronBundle\Entity\GroupTask $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Mrapps\SymCronBundle\Entity\GroupTask
     */
    public function getGroup()
    {
        return $this->group;
    }


    /**
     * Set operation
     *
     * @param string $method
     *
     * @throws \Exception
     *
     * @return Task
     */
    public function setMethod($method)
    {
        if (!in_array($method, Task::VALID_METHODS)) {
            throw new \Exception("Not supported method");
        }

        $this->method = $method;

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setStarted()
    {
        $this->startDateTime = new \DateTime();
        $this->endDateTime = null;
    }

    public function setCompleted()
    {
        $this->endDateTime = new \DateTime();
    }

    public function setSuccess($success)
    {
        $this->success = $success;
    }

    public function isStarted()
    {
        $isStarted = $this->startDateTime != null;

        if ($isStarted) {
            $timeoutDate = clone $this->startDateTime;
            $timeoutDate->add(new \DateInterval('PT1H'));
            return new \DateTime() < $timeoutDate;
        }

        return $isStarted;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function isFailed()
    {
        return $this->isCompleted() && !$this->isSuccess();
    }

    public function isCompleted()
    {
        return $this->endDateTime != null;
    }

    public function canStart()
    {
        return !$this->isStarted()
        || $this->isFailed();
    }
}
