<?php

namespace Polonairs\Dialtime\GateBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class GateExtension extends Extension
{
    public function getAlias()
    {
        return "dialtime/gate";
    }
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter("dialtime.gate.path_to_asterisk", $config["path_to_asterisk"]);
        $container->setParameter("dialtime.gate.asterisk_sip_conf", $config["asterisk_sip_conf"]);
        $container->setParameter("dialtime.gate.modules_conf", $config["modules_conf"]);
        $container->setParameter("dialtime.gate.extensions_conf", $config["extensions_conf"]);
        $container->setParameter("dialtime.gate.agi_app_name", $config["agi_app_name"]);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('gate.xml');
    }
}
