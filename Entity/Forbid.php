<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\ForbidRepository")
 * @ORM\Table(name="forbids")
 */
class Forbid
{
	/** 
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $customer;

    /**
     * @ORM\Column(type="string")
     */
    private $originator;

    /**
     * @ORM\Column(type="string")
     */
    private $master;    

    public function getCustomer()
    {
    	return $this->customer;
    }
    public function getOriginator()
    {
    	return $this->originator;
    }
    public function getMaster()
    {
    	return $this->master;
    }
}
