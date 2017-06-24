<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Entity\Task;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

class TaskRepository extends EntityRepository
{
    public function loadByOrigination(Origination $origination)
    {
        $tasks = $this->getEntityManager()->createQuery("
            SELECT task
            FROM GateBundle:Task task
            LEFT JOIN GateBundle:Forbid forbid WITH task.master = forbid.master
            WHERE task.originator = :originator AND task.state = :state AND forbid.customer <> :customer
            ORDER BY task.rate ASC")
            ->setParameter("originator", $origination->getIncomeDongle())
            ->setparameter("customer", $origination->getCaller())
            ->setParameter("state", Task::STATE_ACTIVE)
            ->getResult();
        if (count($tasks) > 0) return $tasks[0];
        return null;
    }
}
