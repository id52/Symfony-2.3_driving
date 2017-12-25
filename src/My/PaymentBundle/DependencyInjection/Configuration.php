<?php

namespace My\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('payment');

        $root
            ->children()
                ->scalarNode('robokassa_url')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
