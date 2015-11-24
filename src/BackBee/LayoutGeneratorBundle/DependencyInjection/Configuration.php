<?php

namespace BackBee\LayoutGeneratorBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('back_bee_layout_generator');
//        @todo validate that there is only one mainZone=true
        $rootNode
            ->children()
                ->arrayNode('layouts')
                    ->useAttributeAsKey('layoutName')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('template')->end()
                            ->scalarNode('label')->end()
                            ->arrayNode('columns')
                                ->useAttributeAsKey('columName')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('mainZone')->isRequired(true)->end()
                                        ->arrayNode('accept')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->scalarNode('maxentry')->end()
                                        ->scalarNode('defaultClassContent')->end()
                                        ->scalarNode('inherited')->defaultValue(false)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
