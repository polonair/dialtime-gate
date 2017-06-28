<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="gateinfo", indexes={
 *      @ORM\Index(name="key_idx", columns={"key"})
 * })
 */
class GateInfo
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /** @ORM\Column(type="string") */
    private $key;
    /** @ORM\Column(type="string") */
    private $value;
}
