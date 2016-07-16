<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\PhoneRepository")
 * @ORM\Table(name="phones")
 */
class Phone
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
    private $number;
}
