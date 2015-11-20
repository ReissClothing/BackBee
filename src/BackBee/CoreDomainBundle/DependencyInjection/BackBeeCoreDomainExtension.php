<?php

namespace BackBee\CoreDomainBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BackBeeCoreDomainExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bbapp.config.renderer', $config['renderer']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/services'));
        $loader->load('bb.yml');
        $loader->load('new.yml');
        $loader->load('services.yml');
        $loader->load('routing.yml');
        $loader->load('rest.yml');
        $loader->load('site.yml');
        $loader->load('security.yml');

        $container->setParameter('bbapp.classcontent_namespace', $config['classcontent_namespace']);
//        @gvf todo IMO the categories shouldn't be created this way, as it creates all of them in memory even if not used, they should be
//        an entity persisted to doctrine and retrieved from there
        $container->setParameter('bbapp.classcontent_list', $this->classContentList($config['classcontent'], $config['classcontent_namespace']));

        $container->setParameter('bbapp.classcontent_config', $config['classcontent']);
    }

    /**
     * Build and/or hydrate Category object with provided classcontent.
     *
     * @param AbstractClassContent $content
     */
    private function classContentList($classes, $defaultClasscontentNamespace)
    {
        $classList = [];
        foreach($classes as $class => $properties){
            $classList[] = $defaultClasscontentNamespace . $class;
        }

        return $classList;
    }
}
