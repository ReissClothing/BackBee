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
        $streamLoader = new AutoLoader();
        $streamLoader->register();
//        @todo gvf harcoded params figure out a way to have it dynamically search
        $streamLoader->registerStreamWrapper('BackBee\StandardBundle\ClassContent', 'bb.class', 'BackBee\CoreDomainBundle\Stream\ClassWrapper\Adapter\Yaml');
    }

//    public function getParent()
//    {
//        return 'BackBeeWebBundle';
//    }
}
