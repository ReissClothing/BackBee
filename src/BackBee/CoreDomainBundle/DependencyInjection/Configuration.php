<?php

namespace BackBee\CoreDomainBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('back_bee_core_domain');

        $rootNode
            ->children()
                ->arrayNode('renderer')
                    ->children()
                        ->arrayNode('bb_scripts_directory')
                            ->children()
                                ->scalarNode('common')->end()
                                ->scalarNode('form')->end()
                            ->end()
                        ->end()
                        ->arrayNode('adapter')
                            ->children()
                                ->arrayNode('twig')
                                    ->children()
                                        ->scalarNode('class')->end()
                                        ->arrayNode('config')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('phtml')
                                    ->children()
                                        ->scalarNode('class')->end()
                                        ->arrayNode('config')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('path')
                            ->children()
                                ->scalarNode('scriptdir')->end()
                                ->scalarNode('layoutdir')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
