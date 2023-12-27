<?php

use Jbtronics\UserConfigBundle\Manager\ConfigurationRegistry;
use Jbtronics\UserConfigBundle\Manager\ConfigurationRegistryInterface;
use Jbtronics\UserConfigBundle\Schema\SchemaManager;
use Jbtronics\UserConfigBundle\Schema\SchemaManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container
        ->services()
        ->defaults()
        ->private();

    $services->set('jbtronics.user_config.configuration_registry', ConfigurationRegistry::class)
        ->args([
            '$directories' => ['%kernel.project_dir%/src'],
            '$cache' => service('cache.app'),
            '$debug_mode' => '%kernel.debug%',
        ])
        ->tag('kernel.cache_warmer')
    ;
    $services->alias(ConfigurationRegistryInterface::class, 'jbtronics.user_config.configuration_registry');

    $services->set('jbtronics.user_config.schema_manager', SchemaManager::class);
    $services->alias(SchemaManagerInterface::class, 'jbtronics.user_config.schema_manager');
};