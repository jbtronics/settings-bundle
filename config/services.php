<?php

use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $services = $container
        ->services()
        ->defaults()
        ->private();

    $services->set('jbtronics.settings.settings_registry', SettingsRegistry::class)
        ->args([
            '$directories' => ['%kernel.project_dir%/src/Settings/'],
            '$cache' => service('cache.app'),
            '$debug_mode' => '%kernel.debug%',
        ])
        ->tag('kernel.cache_warmer')
    ;
    $services->alias(SettingsRegistryInterface::class, 'jbtronics.settings.settings_registry');

    $services->set('jbtronics.settings.schema_manager', SchemaManager::class)
        ->args([
            '$cache' => service('cache.app'),
            '$debug_mode' => '%kernel.debug%',
        ])
    ;
    $services->alias(SchemaManagerInterface::class, 'jbtronics.settings.schema_manager');

    $services->set('jbtronics.settings.profiler_data_collector', SettingsCollector::class)
        ->tag('data_collector')
        ->args([
            '$configurationRegistry' => service('jbtronics.settings.settings_registry'),
            '$schemaManager' => service('jbtronics.settings.schema_manager'),
        ]);
};