<?php

namespace Polonairs\Dialtime\GateBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Polonairs\Dialtime\GateBundle\Entity\Task;

class TaskRepository extends EntityRepository
{
	public function loadByOriginator($originator)
	{
		$em = $this->getEntityManager();
		$query = $em->createQuery("
			SELECT task 
			FROM GateBundle:Task task
			WHERE task.originator = :originator AND task.state = :state");
		$query
			->setParameter("originator", $originator)
			->setParameter("state", Task::STATE_ACTIVE);
		$tasks = $query->getResult();
		return $tasks;
	}
}
