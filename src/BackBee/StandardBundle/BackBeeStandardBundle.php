<?php

namespace BackBee\StandardBundle;

use BackBee\CoreDomainBundle\AutoLoader\AutoLoader;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BackBeeStandardBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {

        $streamLoader = new AutoLoader($this->container->get('event_dispatcher'), $this->container->getParameter('bbapp.classcontent_namespace'), $this->container->get('bbapp.stream_wrapper.adapter.yml'));
        $streamLoader->register();
//        @todo gvf harcoded params figure out a way to have it dynamically search
//        $streamLoader->registerStreamWrapper('BackBee\CoreDomain\ClassContent', 'bb.class', 'BackBee\CoreDomainBundle\Stream\ClassWrapper\Adapter\Yaml');
//        $streamLoader->registerStreamWrapper('BackBee\CoreDomain\ClassContent', 'bb.class', );
    }

    public function getParent()
    {
        return 'BackBeeWebBundle';
    }
}
