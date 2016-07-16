<?php

namespace Polonairs\Dialtime\GateBundle\Agi;

use Polonairs\Dialtime\GateBundle\Entity\Call;
use Polonairs\Dialtime\GateBundle\Entity\Route;
use Polonairs\Dialtime\GateBundle\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class Router
{
	private $doctrine = null;

	public function __construct(Doctrine $doctrine)
	{
		$this->doctrine = $doctrine;
	}
	public function process(Agi $agi)
	{
		$origination = $agi->getOrigination();
		$em = $this->doctrine->getManager();
		
//		dump($agi);

		$phone = $em->getRepository("GateBundle:Phone")->loadByNumber($origination->getCaller());
		$route = $em->getRepository("GateBundle:Route")->loadByOrigination($origination);
		
//		dump($phone);
//		dump($route);

		if ($phone !== null)
		{
			if ($route->getDirection($origination) === Call::DIRECTION_MO)
			{
				//echo "we found a route\r\n";
				return (new Call())
					->setRoute($route)
					->setDirection($route->getDirection($origination));
			}
		}
		$spammer = $em->getRepository("GateBundle:Forbid")->loadSpammerByNumber($origination->getCaller()); 
		if ($spammer === null)
		{
			$forbids = $em->getRepository("GateBundle:Forbid")->loadByNumber($origination->getCaller());
			if (count($forbids) > 0) 
			{
				foreach ($forbids as $forbid) 
				{
					if ($origination->isForbidden($forbid)) 
					{
//						echo "we found a fully forbidden call\r\n";
						return (new Call())
							->setRoute((new Route())
								->setCustomer($origination->getCaller())
								->setOriginator($origination->getIncomeDongle())
								->setState(Route::STATE_FORBIDDEN)
								->setExpiredAt(new \DateTime("now")))
							->setDirection(Call::DIRECTION_MT);				
					}
				}		
			}
			if ($route !== null)
			{
//				echo "we found a route\r\n";
				return (new Call())
					->setRoute($route)
					->setDirection($route->getDirection($origination));
			}
			$tasks = $em->getRepository("GateBundle:Task")->loadByOriginator($origination->getIncomeDongle());			
			if (count($tasks) > 0)
			{
				foreach ($tasks as $task) 
				{
					if (!$task->isForbidden($forbids, $origination))
					{
//						echo "we found a task\r\n";
						$task->setState(Task::STATE_DONE);// done for all of sid and master
						$em->flush();
						return (new Call())
							->setRoute((new Route())
								->setCustomer($origination->getCaller())
								->setOriginator($origination->getIncomeDongle())
								->setTerminator($agi->selectTerminator($task->getTerminators()))
								->setMaster($task->getMaster())
								->setTaskId($task->getSid())
								->setState(Route::STATE_RG)
								->setExpiredAt((new \DateTime("now"))->add(new \DateInterval($task->getActiveInterval()))))
							->setDirection(Call::DIRECTION_RG);
					}
				}
//				echo "all tasks are forbidden\r\n";
				return (new Call())
					->setRoute((new Route())
						->setCustomer($origination->getCaller())
						->setOriginator($origination->getIncomeDongle())
						->setState(Route::STATE_FORBIDDEN_TASKS)
						->setExpiredAt(new \DateTime("now")))
					->setDirection(Call::DIRECTION_RG);
			}
//			echo "we found neither routes nor tasks\r\n";
			return (new Call())
				->setRoute((new Route())
					->setCustomer($origination->getCaller())
					->setOriginator($origination->getIncomeDongle())
					->setState(Route::STATE_NO_WAY)
					->setExpiredAt(new \DateTime("now")))
				->setDirection(Call::DIRECTION_UNKNOWN);
		}
		else
		{
//			echo "we found a spammer\r\n";
			return (new Call())
				->setRoute((new Route())
					->setCustomer($origination->getCaller())
					->setOriginator($origination->getIncomeDongle())
					->setState(Route::STATE_SPAM)
					->setExpiredAt(new \DateTime("now")))
				->setDirection(Call::DIRECTION_UNKNOWN);
		}
	}
}
