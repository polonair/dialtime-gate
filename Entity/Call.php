<?php

namespace Polonairs\Dialtime\GateBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Polonairs\Dialtime\GateBundle\Repository\CallRepository")
 * @ORM\Table(name="calls")
 */
class Call
{
	const DIRECTION_MO = "MO";
	const DIRECTION_MT = "MT";
	const DIRECTION_RG = "RG";
	const DIRECTION_UNKNOWN = "UNKNOWN";

    const RESULT_CANCEL= "CANCEL";
    const RESULT_ANSWER= "ANSWER";

	/** 
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Route")
     */
    private $route = null;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $direction;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $result;

    /**
     * @ORM\Column(type="integer")
     */
    private $dial_length = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $answer_length = 0;

    /**
     * @ORM\Column(type="blob", nullable=true)
     */
    private $record = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;
    private $hash;

    public function __construct()
    {
        $this->created_at = new \DateTime("now");
        $this->hash = md5(json_encode($this->created_at).microtime()."call");
    }
    public function getRoute()
    {
    	return $this->route;
    }
    public function getHash()
    {
        return $this->hash;
        //return md5(json_encode($this->created_at).microtime()."call");
    }
    public function getResult()
    {
        return $this->result;
    }
    public function getDirection()
    {
        return $this->direction;
    }

    public function setRoute(Route $route)
    {
    	$this->route = $route;
    	return $this;
    }
    public function setDirection($direction)
    {
    	$this->direction = $direction;
    	return $this;
    }
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }
    public function setDialLength($length)
    {
        $this->dial_length = $length;
        return $this;
    }
    public function setAnswerLength($length)
    {
        $this->answer_length = $length;
        return $this;
    }
    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }
}
