<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Agi\Origination;
use Polonairs\Dialtime\GateBundle\Entity\Route;

class RouteRepository extends EntityRepository
{
	public function loadByOrigination(Origination $origination)
	{
		$em = $this->getEntityManager();
		$query = $em->createQuery("
			SELECT route 
			FROM GateBundle:Route route
			WHERE 
				route.state IN (:states) AND
				((route.customer = :caller AND route.originator = :dongle) OR 
				(route.master = :caller AND route.terminator = :dongle))");
		$query
			->setParameter("states", [Route::STATE_ACTIVE])
			->setParameter("caller", $origination->getCaller())
			->setParameter("dongle", $origination->getIncomeDongle());
		$routes = $query->getResult();
		if (count($routes) === 0) return null;
		return $routes[0];
	}
}
