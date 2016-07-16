<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\DongleRepository")
 * @ORM\Table(name="dongles")
 */
class Dongle
{
	const STATE_ACTIVE = "ACTIVE";
	
	/** 
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 */
    private $id;
	/**
	 * @ORM\Column(type="string")
	 */
	private $state;
	/**
	 * @ORM\Column(type="string")
	 */
	private $number;
	/**
	 * @ORM\Column(type="string")
	 */
	private $voice_password;
	/**
	 * @ORM\Column(type="datetime")
	 */
	private $created_at;

	public function getNumber()
	{
		return $this->number;
	}
	public function getVoicePassword()
	{
		return $this->voice_password;
	}
}
