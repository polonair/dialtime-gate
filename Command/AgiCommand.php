<?php

namespace Polonairs\Dialtime\GateBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Polonairs\Dialtime\GateBundle\Agi\Agi;
use Polonairs\Dialtime\GateBundle\Agi\Router;
use Polonairs\Dialtime\GateBundle\Agi\Origination;

use Polonairs\Dialtime\GateBundle\Entity\Route;

class AgiCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('dialtime:gate:agi')->setDescription('AGI');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $router = $this->getContainer()->get('dialtime.gate.call_router');
        $agi = $this->getContainer()->get('dialtime.gate.agi')->init();
        $agi->noop("start");

        while(true)
        {
            $call = $router->process($agi);
            $agi->dial($call);
            $em->persist($call->getRoute());
            $em->persist($call);
            $em->flush();
            return;
        }
    }
}
