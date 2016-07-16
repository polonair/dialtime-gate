<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Entity\Dongle;

class DongleRepository extends EntityRepository
{
	public function loadActive()
	{
		$em = $this->getEntityManager();
		$dongles = $em->createQuery("
			SELECT dongle
			FROM GateBundle:Dongle dongle
			INDEX BY dongle.id
			WHERE dongle.state IN (:states) AND dongle.created_at < :now")
			/* ------------------------------------------------------ */
			->setParameter("states", [Dongle::STATE_ACTIVE])
			->setParameter("now", new \DateTime("now"))
			->getResult();
		return $dongles;
	}
}
