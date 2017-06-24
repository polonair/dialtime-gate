<?php

namespace Polonairs\Dialtime\GateBundle\Cron;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class AsteriskConfigurer
{
    private $doctrine = null;
    private $modules;
    private $extensions;
    private $agi_app_name;

    public function __construct(Doctrine $doctrine, $modules, $extensions, $agi_app_name)
    {
        $this->doctrine = $doctrine;
        $this->modules = $modules;
        $this->extensions = $extensions;
        $this->agi_app_name = $agi_app_name;
    }
    public function update()
    {
        return $this->update_modules() || $this->update_extensions();
        $em = $this->doctrine->getManager();
        $dongles = $em->getRepository("GateBundle:Dongle")->loadActive();
        $new_content = $this->createContent($dongles);
        $old_content = $this->findContent();
        if ($new_content !== $old_content) $this->saveContent($new_content);
    }
    private function update_modules()
    {
        $content = <<<EOA
[modules]

autoload=yes
noload => pbx_gtkconsole.so
noload => pbx_kdeconsole.so
noload => app_intercom.so
noload => chan_modem.so
noload => chan_modem_aopen.so
noload => chan_modem_bestdata.so
noload => chan_modem_i4l.so
noload => chan_capi.so
load => res_musiconhold.so
noload => chan_alsa.so
noload => cdr_sqlite.so
noload => cdr_radius.so
noload => cel_radius.so

[global]

noload => chan_oh323.so
noload => chan_h323.so

EOA;
        return $this->_update($content, $this->modules);
    }
    private function update_extensions()
    {
        $script_name = dirname(realpath($_SERVER['SCRIPT_FILENAME']))."/".$this->agi_app_name;
        $content = <<<EOB
[default]

exten=>s,1,Hangup()

[income]

exten=> _7X.,1,AGI($script_name)

EOB;
        return $this->_update($content, $this->extensions);
    }
    private function _update($content, $filename)
    {
        if (file_exists($filename)) $old_content = file_get_contents($filename);
        else $old_content = "";
        if ($old_content !== $content)
        {
            file_put_contents($filename, $content);
            return true;
        }
        return false;
    }
}
