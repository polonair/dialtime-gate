<?php

namespace Polonairs\Dialtime\GateBundle\Agi;

use Polonairs\Dialtime\GateBundle\Entity\Forbid;

class Origination
{
	private $caller;
	private $incomeDongle;

	public function __construct(array $request)
//	public function __construct($caller, $incomeDongle)
	{
		$this->caller = $request["agi_callerid"];
		$this->incomeDongle = $request["agi_dnid"];
	}
	public function getCaller()
	{
		return $this->caller;
	}
	public function getIncomeDongle()
	{
		return $this->incomeDongle;
	}
	public function isForbidden(Forbid $forbid)
	{
		if ($forbid->getCustomer() === $this->caller &&
			$forbid->getOriginator() === $this->incomeDongle)
			return true;
		return false;
	}
}
