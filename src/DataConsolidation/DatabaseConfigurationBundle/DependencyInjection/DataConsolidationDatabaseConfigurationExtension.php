<?php

namespace DataConsolidation\DatabaseConfigurationBundle\DependencyInjection;

use DataConsolidation\DatabaseConfigurationBundle\Utils\DatabaseConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DataConsolidationDatabaseConfigurationExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('doctrine');
        if (!isset($configs['0'])) {
            // Seems like the extension config for doctrine could not be fetched.
            return;
        }
        // Select the first element of the doctrine configs.
        $config = reset($configs);

        // Get a DatabaseConfigurator instance directly.
        // The DatabaseConfigurator service cannot be obtained through the container
        // because it is not fully compiled yet.
        $databaseConfigurator = new DatabaseConfigurator();

        // Read the custom parameters config defined in this bundle.
        $parametersConfig = $databaseConfigurator->getDatabaseParametersValue();
        if (!isset($parametersConfig['parameters'])) {
            // Invalid parameters config. Do not do anything else.
            return;
        }
        // Register parameter values for the container.
        foreach ($parametersConfig['parameters'] as $parameterName => $value) {
            $container->setParameter($parameterName, $value);
        }

        // Read the custom database config defined in this bundle.
        $databaseConfig = $databaseConfigurator->getDatabaseConfigValue();
        if (!isset($databaseConfig['doctrine'])) {
            // Invalid database config. Do not do anything else.
            return;
        }
        // Merge the existing doctrine config with the custom one defined in this bundle.
        $config = array_merge_recursive($config, $databaseConfig['doctrine']);
        // Prepend the extension config, effectively modifying doctrine's config.
        $container->prependExtensionConfig('doctrine', $config);
    }

}
