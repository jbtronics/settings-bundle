<?php

use Jbtronics\SettingsBundle\DependencyInjection\SettingsExtension;
use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistry;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private()
        ->instanceof(ParameterTypeInterface::class)->tag(SettingsExtension::TAG_PARAMETER_TYPE)
        ;


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

    $services->set('jbtronics.settings.settings_manager', SettingsManager::class)
        ->args([
            '$schemaManager' => service('jbtronics.settings.schema_manager'),
            '$settingsRegistry' => service('jbtronics.settings.settings_registry'),
        ])
        ;
    $services->alias(SettingsManagerInterface::class, 'jbtronics.settings.settings_manager');

    $services->set('jbtronics.settings.parameter_type_registry', ParameterTypeRegistry::class)
        ->args([
            '$locator' => tagged_locator(SettingsExtension::TAG_PARAMETER_TYPE)
        ])
        ;
    $services->alias(ParameterTypeRegistryInterface::class, 'jbtronics.settings.parameter_type_registry');

    $services->set('jbtronics.settings.profiler_data_collector', SettingsCollector::class)
        ->tag('data_collector')
        ->args([
            '$configurationRegistry' => service('jbtronics.settings.settings_registry'),
            '$schemaManager' => service('jbtronics.settings.schema_manager'),
            '$settingsManager' => service('jbtronics.settings.settings_manager'),
        ]);

    /**********************************************************************************
     * Parameter Types
     **********************************************************************************/
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\IntType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\StringType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\BoolType::class);
};