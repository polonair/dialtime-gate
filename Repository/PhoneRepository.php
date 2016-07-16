<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PhoneRepository extends EntityRepository
{
	public function loadByNumber($number)
	{
		$em = $this->getEntityManager();
		$query = $em->createQuery("
			SELECT phone
			FROM GateBundle:Phone phone
			WHERE phone.number = :number");
		$query->setParameter("number", $number);
		$phones = $query->getResult();
		if (count($phones) === 0) return null;
		return $phones[0];
	}
}
