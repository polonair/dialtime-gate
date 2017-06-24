<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Entity\Spammer;

class SpammerRepository extends EntityRepository
{
	public function loadByNumber($number)
	{
		$spammers = $this->getEntityManager()->createQuery("
			SELECT spammer
			FROM GateBundle:Spammer spammer
			WHERE spammer.number = :number")
			->setParameter("number", $number)
			->getResult();
		if (count($spammers) > 0) return $spammers[0];
		return null;
	}
}
