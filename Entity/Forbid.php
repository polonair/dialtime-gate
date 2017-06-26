<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="forbids", indexes={
 *      @ORM\Index(name="customer_idx", columns={"customer"}),
 *      @ORM\Index(name="master_idx", columns={"master"})
 * })
 */
class Forbid
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @ORM\Column(type="string") */
    private $customer;
    /** @ORM\Column(type="string") */
    private $master;
}
