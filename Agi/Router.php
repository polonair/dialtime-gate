<?php

namespace Polonairs\Dialtime\GateBundle\Agi;

use Polonairs\Dialtime\GateBundle\Entity\Call;
use Polonairs\Dialtime\GateBundle\Entity\Route;
use Polonairs\Dialtime\GateBundle\Entity\Master;
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

        $master = $em->getRepository("GateBundle:Master")->loadByNumber($origination->getCaller());

        if ($master !== null)
        {
            $route = $em->getRepository("GateBundle:Route")->loadByTermination($origination);
            if ($route !== null)
            {
                return (new Call())
                    ->setRoute($route)
                    ->setDirection(Call::DIRECTION_MO);
            }
            else
            {
                return (new Call())
                    ->setRoute((new Route())
                        ->setMaster($origination->getCaller())
                        ->setTerminator($origination->getIncomeDongle())
                        ->setState(Route::STATE_INACTIVE))
                    ->setDirection(Call::DIRECTION_MO);
            }
        }
        else
        {
            $spammer = $em->getRepository("GateBundle:Spammer")->loadByNumber($origination->getCaller());
            if ($spammer !== null)
            {
                return (new Call())
                    ->setRoute((new Route())
                        ->setCustomer($origination->getCaller())
                        ->setOriginator($origination->getIncomeDongle())
                        ->setState(Route::STATE_INACTIVE))
                    ->setDirection(Call::DIRECTION_MT);
            }
            else
            {
                $route = $em->getRepository("GateBundle:Route")->loadByOrigination($origination);
                if ($route !== null)
                {
                    return (new Call())
                        ->setRoute($route
                            ->setState(Route::STATE_ACTIVE))
                        ->setDirection(Call::DIRECTION_MT);
                }
                else
                {
                    $task = $em->getRepository("GateBundle:Task")->loadByOrigination($origination);
                    if ($task !== null)
                    {
                        $task->setState(Task::STATE_DONE);
                        $em->flush();
                        return (new Call())
                            ->setRoute((new Route())
                                ->setCustomer($origination->getCaller())
                                ->setOriginator($origination->getIncomeDongle())
                                ->setTerminator($agi->selectTerminator($task->getTerminators()))
                                ->setMaster($task->getMaster())
                                ->setTaskId($task->getSid())
                                ->setState(Route::STATE_ACTIVE))
                            ->setDirection(Call::DIRECTION_RG);
                    }
                    else
                    {
                        $route = $em->getRepository("GateBundle:Route")->loadByIncomeDongle($origination->getIncomeDongle());
                        if ($route !== null)
                        {
                            return (new Call())
                                ->setRoute((new Route())
                                    ->setCustomer($origination->getCaller())
                                    ->setOriginator($route->getOriginator())
                                    ->setTerminator($route->getTerminator())
                                    ->setMaster($route->getMaster())
                                    ->setState(Route::STATE_INACTIVE))
                                ->setDirection(Call::DIRECTION_DR);
                        }
                        else
                        {
                            return (new Call())
                                ->setRoute((new Route())
                                    ->setCustomer($origination->getCaller())
                                    ->setOriginator($origination->getIncomeDongle())
                                    ->setState(Route::STATE_INACTIVE))
                                ->setDirection(Call::DIRECTION_MT);
                        }
                    }
                }
            }
        }
        return null;
    }
}
