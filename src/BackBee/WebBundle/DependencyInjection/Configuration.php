<?php

namespace BackBee\WebBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('back_bee_web');

        $rootNode
            ->children()
                ->arrayNode('toolbar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('wrapper_toolbar_id')
                            ->defaultValue('bb5-ui')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->booleanNode('disable_toolbar')
                            ->defaultValue(false)
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
