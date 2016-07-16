<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\TaskRepository")
 * @ORM\Table(name="tasks")
 */
class Task
{
    const STATE_DONE = "DONE";
    const STATE_ACTIVE = "ACTIVE";

    /** 
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** 
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sid = null;
    
    /**
     * @ORM\Column(type="string")
     */
    private $state;

    /**
     * @ORM\Column(type="string")
     */
    private $originator;

    /**
     * @ORM\Column(type="string")
     */
    private $master;

    /**
     * @ORM\Column(type="json_array")
     */
    private $terminators;

    /**
     * @ORM\Column(type="string")
     */
    private $active_interval;

    public function getMaster()
    {
        return $this->master;
    }
    public function getTerminators()
    {
        return $this->terminators;
    }
    public function getActiveInterval()
    {
        return $this->active_interval;
    }
    public function getSid()
    {
        return $this->sid;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function isForbidden($forbids, Origination $origination)
    {
        foreach($forbids as $forbid)
            if ($forbid->getCustomer() === $origination->getCaller() &&
                $forbid->getMaster() === $this->master)
                return true;
        return false;
    }
}
