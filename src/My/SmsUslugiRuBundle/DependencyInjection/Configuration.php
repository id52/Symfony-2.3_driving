<?php

namespace My\SmsUslugiRuBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('sms_uslugi_ru');

        $root
            ->children()
                ->scalarNode('url')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
