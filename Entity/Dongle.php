<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\DongleRepository")
 * @ORM\Table(name="dongles", indexes={
 *      @ORM\Index(name="state_idx", columns={"state"}),
 *      @ORM\Index(name="number_idx", columns={"number"}),
 *      @ORM\Index(name="created_at_idx", columns={"created_at"})
 * })
 */
class Dongle
{
    const STATE_ACTIVE = "ACTIVE";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id;
    /** @ORM\Column(type="string") */
    private $state;
    /** @ORM\Column(type="string") */
    private $number;
    /** @ORM\Column(type="string") */
    private $voice_password;
    /** @ORM\Column(type="datetime") */
    private $created_at;

    public function getNumber() { return $this->number; }
    public function getVoicePassword() { return $this->voice_password; }
}
