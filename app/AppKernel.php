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
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new SymfonyContrib\Bundle\ConfirmBundle\ConfirmBundle(),
            new Craue\FormFlowBundle\CraueFormFlowBundle(),
            new AppBundle\AppBundle(),
            new AirMeasurement\DiagramBundle\AirMeasurementDiagramBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new AirMeasurement\UserBundle\AirMeasurementUserBundle(),
            new AirMeasurement\AdminBundle\AirMeasurementAdminBundle(),
            new DataConsolidation\CustomNodesBundle\DataConsolidationCustomNodesBundle(),
            new DataConsolidation\DatabaseConfigurationBundle\DataConsolidationDatabaseConfigurationBundle(),
            new DataConsolidation\DiagramDataFetcherBundle\DataConsolidationDiagramDataFetcherBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Acme\DemoBundle\AcmeDemoBundle();
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
}
