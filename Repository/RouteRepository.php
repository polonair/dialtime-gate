<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Agi\Origination;
use Polonairs\Dialtime\GateBundle\Entity\Route;

class RouteRepository extends EntityRepository
{
    public function loadByOrigination(Origination $origination)
    {
        $routes = $this->getEntityManager()->createQuery("
            SELECT route
            FROM GateBundle:Route route
            WHERE
                route.state IN (:states) AND
                route.customer = :caller AND
                route.originator = :dongle")
            ->setParameter("states", [Route::STATE_ACTIVE, Route::STATE_REACTIVE])
            ->setParameter("caller", $origination->getCaller())
            ->setParameter("dongle", $origination->getIncomeDongle())
            ->getResult();
        if (count($routes) > 0) return $routes[0];
        return null;
    }
    public function loadByTermination(Origination $origination)
    {
        $routes = $this->getEntityManager()->createQuery("
            SELECT route
            FROM GateBundle:Route route
            WHERE
                route.state IN (:states) AND
                route.master = :caller AND
                route.terminator = :dongle")
            ->setParameter("states", [Route::STATE_ACTIVE])
            ->setParameter("caller", $origination->getCaller())
            ->setParameter("dongle", $origination->getIncomeDongle())
            ->getResult();
        if (count($routes) > 0) return $routes[0];
        return null;
    }
    public function loadByIncomeDongle(Origination $origination)
    {
        $routes = $this->getEntityManager()->createQuery("
            SELECT route
            FROM GateBundle:Route route
            WHERE
                route.state IN (:states) AND
                route.customer = IS NULL AND
                route.terminator = :dongle")
            ->setParameter("states", [Route::STATE_ACTIVE])
            ->setParameter("dongle", $origination->getIncomeDongle())
            ->getResult();
        if (count($routes) > 0) return $routes[0];
        return null;
    }
}
