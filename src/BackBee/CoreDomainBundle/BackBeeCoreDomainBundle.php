<?php

namespace BackBee\CoreDomainBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BackBeeCoreDomainBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        foreach ($this->getModelNamespace() as $modelNameSpace) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                array($modelNameSpace[0]),
                array($modelNameSpace[1])
            ));
        }
    }

    protected function getModelNamespace()
    {
        return [
            ['BackBee\CoreDomain\Site', sprintf('%s/../CoreDomain/Site', $this->getPath())],
            ['BackBee\CoreDomain\NestedNode', sprintf('%s/../CoreDomain/NestedNode', $this->getPath())],
            ['BackBee\CoreDomain\NestedNode\Repository', sprintf('%s/../CoreDomain/NestedNode/Repository', $this->getPath())],
            ['BackBee\CoreDomain\Workflow', sprintf('%s/../CoreDomain/Workflow', $this->getPath())],
            ['BackBee\CoreDomain\ClassContent', sprintf('%s/../CoreDomain/ClassContent', $this->getPath())],
            ['BackBee\CoreDomain\Security', sprintf('%s/../CoreDomain/Security', $this->getPath())],
        ];
    }
}
