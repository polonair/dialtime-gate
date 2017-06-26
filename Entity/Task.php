<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\TaskRepository")
 * @ORM\Table(name="tasks", indexes={
 *      @ORM\Index(name="sid_idx", columns={"sid"}),
 *      @ORM\Index(name="state_idx", columns={"state"}),
 *      @ORM\Index(name="originator_idx", columns={"originator"}),
 *      @ORM\Index(name="master_idx", columns={"master"})
 * })
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
    /** @ORM\Column(type="integer", nullable=true) */
    private $sid = null;
    /** @ORM\Column(type="string") */
    private $state;
    /** @ORM\Column(type="decimal", precision=11, scale=2) */
    private $rate;
    /** @ORM\Column(type="string") */
    private $originator;
    /** @ORM\Column(type="string") */
    private $master;
    /** @ORM\Column(type="json_array") */
    private $terminators;
    /** @ORM\Column(type="string") */
    private $active_interval;

    public function getTerminators() { return $this->terminators; }
    public function getSid() { return $this->sid; }
    public function getMaster() { return $this->master; }
    public function setState($state) { $this->state = $state; return $this; }
}
