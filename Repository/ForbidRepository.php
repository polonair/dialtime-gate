<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ForbidRepository extends EntityRepository
{	
	public function loadByNumber($number)
	{
		$em = $this->getEntityManager();
		$query = $em->createQuery("
			SELECT forbid
			FROM GateBundle:Forbid forbid
			WHERE forbid.customer = :number");
		$query->setParameter("number", $number);
		$forbids = $query->getResult();
		return $forbids;
	}
	public function loadSpammerByNumber($number)
	{
		$em = $this->getEntityManager();
		$query = $em->createQuery("
			SELECT forbid
			FROM GateBundle:Forbid forbid
			WHERE 
				forbid.customer = :number AND 
				forbid.master IS NULL AND 
				forbid.originator IS NULL");
		$query->setParameter("number", $number);
		$forbids = $query->getResult();
		if (count($forbids) === 0) return null;
		return $forbids[0];
	}
}
