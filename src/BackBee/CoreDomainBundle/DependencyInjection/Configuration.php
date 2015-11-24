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
                ->scalarNode('classcontent_namespace')->defaultValue('BackBee\CoreDomain\ClassContent\\')->end()
                ->arrayNode('classcontent')
                    ->useAttributeAsKey('classname')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('repository')->end()
                            ->variableNode('traits')->end()
                            ->scalarNode('extends')->end()
                            ->arrayNode('properties')
                                ->children()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('description')->end()
                                    ->arrayNode('category')
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->arrayNode('indexation')
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->scalarNode('labelized_by')->end()
                                    ->scalarNode('iconized_by')->end()
                                    ->scalarNode('clonemode')->end()
                                    ->scalarNode('cache_lifetime')->end()
                                ->end()
                            ->end()
                            ->variableNode('elements')->end()
                            ->variableNode('parameters')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
