<?php

namespace Polonairs\Dialtime\GateBundle\Cron;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class Cronner
{
    private $doctrine = null;
    private $asterisk;
    private $sip_conf;
    private $modules_conf;
    private $extensions_conf;
    private $agi_app_name;

    public function __construct(Doctrine $doctrine, $asterisk, $sip_conf, $modules_conf, $extensions_conf, $agi_app_name)
    {
        $this->doctrine = $doctrine;
        $this->asterisk = $asterisk;
        $this->sip_conf = $sip_conf;
        $this->modules_conf = $modules_conf;
        $this->extensions_conf = $extensions_conf;
        $this->agi_app_name = $agi_app_name;
    }
    public function process()
    {
        $do_restart = false
            || (new Dongler($this->doctrine, $this->sip_conf))->update()
            || (new AsteriskConfigurer($this->doctrine, $this->modules_conf, $this->extensions_conf, $this->agi_app_name))->update();
        if ($do_restart) $this->restartAsterisk();
    }
    private function restartAsterisk()
    {
        exec($this->asterisk . " -x 'core restart gracefully'");
    }
}
