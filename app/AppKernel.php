<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new BackBee\CoreDomainBundle\BackBeeCoreDomainBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new BackBee\WebBundle\BackBeeWebBundle(),
            new BackBee\StandardBundle\BackBeeStandardBundle(),
            new BackBee\ApiBundle\BackBeeApiBundle(),
            new BackBee\ToolbarBundle\BackBeeToolbarBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new BackBee\LayoutGeneratorBundle\BackBeeLayoutGeneratorBundle(),
            new \Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    /**
     * In case you prefer to use /dev/shm just setenv USESHM to yes
     */
    public function getCacheDir()
    {
//        if ($this->shouldUseSharedMemory()) {
            return '/dev/shm/bb-symfony/cache/' . $this->environment;
//        }

        return $this->rootDir . '/cache/' . $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
//        if ($this->shouldUseSharedMemory()) {
            return '/dev/shm/bb-symfony/logs/' . $this->environment;
//        }

        return $this->rootDir . '/logs/' . $this->environment;
    }


    /**
     * @return boolean
     */
    protected function shouldUseSharedMemory()
    {
        return getenv('USESHM') === 'yes' && is_dir('/dev/shm');
    }
}
