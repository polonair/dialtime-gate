<?php

namespace Polonairs\Dialtime\GateBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('dialtime:gate:cron')->setDescription('CRON');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('dialtime.gate.cron')->process();
    }
}
