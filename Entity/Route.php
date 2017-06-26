<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\RouteRepository")
 * @ORM\Table(name="routes", indexes={
 *      @ORM\Index(name="sid_idx", columns={"sid"}),
 *      @ORM\Index(name="task_id_idx", columns={"task_id"}),
 *      @ORM\Index(name="state_idx", columns={"state"}),
 *      @ORM\Index(name="customer_idx", columns={"customer"}),
 *      @ORM\Index(name="originator_idx", columns={"originator"}),
 *      @ORM\Index(name="master_idx", columns={"master"}),
 *      @ORM\Index(name="terminator_idx", columns={"terminator"})
 * })
 */
class Route
{
    const STATE_ACTIVE = "ACTIVE";
    const STATE_INACTIVE = "INACTIVE";
    const STATE_REACTIVE = "REACTIVE";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @ORM\Column(type="integer", nullable=true) */
    private $sid = null;
    /** @ORM\Column(type="integer", nullable=true) */
    private $task_id = null;
    /** @ORM\Column(type="string") */
    private $state;
    /** @ORM\Column(type="string", nullable=true) */
    private $customer = null;
    /** @ORM\Column(type="string", nullable=true) */
    private $originator = null;
    /** @ORM\Column(type="string", nullable=true) */
    private $master = null;
    /** @ORM\Column(type="string", nullable=true) */
    private $terminator = null;
    /** @ORM\Column(type="datetime") */
    private $created_at;

    public function __construct() { $this->created_at = new \DateTime("now"); }

    public function getTerminator() { return $this->terminator; }
    public function getMaster() { return $this->master; }
    public function getOriginator() { return $this->originator; }
    public function getCustomer() { return $this->customer; }
    public function getState() { return $this->state; }

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
}
