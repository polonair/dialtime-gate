<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Entity\Master;

class MasterRepository extends EntityRepository
{
	public function loadByNumber($number)
	{
		$masters = $this->getEntityManager()->createQuery("
			SELECT master
			FROM GateBundle:Master master
			WHERE master.number = :number")
			->setParameter("number", $number)
			->getResult();
		if (count($masters) > 0) return $masters[0];
		return null;
	}
}
