<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\RouteRepository")
 * @ORM\Table(name="routes")
 */
class Route
{
    const STATE_FORBIDDEN_TASKS = "FORBIDDEN_TASKS";
    const STATE_FORBIDDEN = "FORBIDDEN";
	const STATE_ACTIVE = "ACTIVE";
    const STATE_NO_WAY = "NO_WAY";
    const STATE_SPAM = "SPAM";
    const STATE_RG = "RG";

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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $task_id = null;

    /**
     * @ORM\Column(type="string")
     */
    private $state;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     */
	private $customer = null;
	
    /**
     * @ORM\Column(type="string", nullable=true)
     */
	private $originator = null;
	
    /**
     * @ORM\Column(type="string", nullable=true)
     */
	private $master = null;
	
    /**
     * @ORM\Column(type="string", nullable=true)
     */
	private $terminator = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expired_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime("now");
    }

    public function getState()
    {
        return $this->state;
    }
    public function getDirection(Origination $origination)
    {
        if ($origination->getCaller() === $this->master &&
            $origination->getIncomeDongle() === $this->terminator)
            return Call::DIRECTION_MO;
        if ($origination->getCaller() === $this->customer &&
            $origination->getIncomeDongle() === $this->originator)
            return Call::DIRECTION_MT;
    }
    public function getTerminator()
    {
        return $this->terminator;
    }
    public function getMaster()
    {
        return $this->master;
    }
    public function getOriginator()
    {
        return $this->originator;
    }
    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }
    public function setMaster($master)
    {
        $this->master = $master;
        return $this;
    }
    public function setTaskId($task_id)
    {
        $this->task_id = $task_id;
        return $this;        
    }
    public function setOriginator($originator)
    {
        $this->originator = $originator;
        return $this;
    }
    public function setTerminator($terminator)
    {
        $this->terminator = $terminator;
        return $this;
    }
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }
    public function setExpiredAt(\DateTime $expiration)
    {
        $this->expired_at = $expiration;
        return $this;
    }
}
