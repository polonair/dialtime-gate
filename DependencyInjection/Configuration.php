<?php

namespace Polonairs\Dialtime\GateBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('dialtime/gate');

        $rootNode
            ->children()
                ->scalarNode('path_to_asterisk')
                    ->defaultValue('/usr/sbin/asterisk')
                ->end()
            ->end()
            ->children()
                ->scalarNode('asterisk_sip_conf')
                    ->defaultValue('/etc/asterisk/sip.conf')
                ->end()
            ->end()
            ->children()
                ->scalarNode('modules_conf')
                    ->defaultValue('/etc/asterisk/modules.conf')
                ->end()
            ->end()
            ->children()
                ->scalarNode('extensions_conf')
                    ->defaultValue('/etc/asterisk/extensions.conf')
                ->end()
            ->end()
            ->children()
                ->scalarNode('agi_app_name')
                    ->defaultValue('dialtime-agi')
                ->end()
            ->end()
            ->children()
                ->scalarNode('le')
                    ->defaultValue("\n")
                ->end()
            ->end();

        return $treeBuilder;
    }
}
