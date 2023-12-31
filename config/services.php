<?php

use Jbtronics\SettingsBundle\DependencyInjection\SettingsExtension;
use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Manager\SettingsResetter;
use Jbtronics\SettingsBundle\Manager\SettingsResetterInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistry;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistry;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private()
        ->instanceof(ParameterTypeInterface::class)->tag(SettingsExtension::TAG_PARAMETER_TYPE)
        ->instanceof(StorageAdapterInterface::class)->tag(SettingsExtension::TAG_STORAGE_ADAPTER)
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
            '$settingsHydrator' => service('jbtronics.settings.settings_hydrator'),
            '$settingsResetter' => service('jbtronics.settings.settings_resetter'),
            '$settingsValidator' => service('jbtronics.settings.settings_validator'),
        ])
        ;
    $services->alias(SettingsManagerInterface::class, 'jbtronics.settings.settings_manager');

    $services->set('jbtronics.settings.parameter_type_registry', ParameterTypeRegistry::class)
        ->args([
            '$locator' => tagged_locator(SettingsExtension::TAG_PARAMETER_TYPE)
        ])
        ;
    $services->alias(ParameterTypeRegistryInterface::class, 'jbtronics.settings.parameter_type_registry');

    $services->set('jbtronics.settings.storage_adapter_registry', StorageAdapterRegistry::class)
        ->args([
            '$locator' => tagged_locator(SettingsExtension::TAG_STORAGE_ADAPTER)
        ])
        ;
    $services->alias(StorageAdapterRegistryInterface::class, 'jbtronics.settings.storage_adapter_registry');

    $services->set('jbtronics.settings.settings_hydrator', SettingsHydrator::class)
        ->args([
            '$storageAdapterRegistry' => service('jbtronics.settings.storage_adapter_registry'),
            '$parameterTypeRegistry' => service('jbtronics.settings.parameter_type_registry'),
        ])
        ;
    $services->alias(SettingsHydratorInterface::class, 'jbtronics.settings.settings_hydrator');

    $services->set('jbtronics.settings.settings_resetter', SettingsResetter::class);
    $services->alias(SettingsResetterInterface::class, 'jbtronics.settings.settings_resetter');

    $services->set('jbtronics.settings.settings_validator', \Jbtronics\SettingsBundle\Manager\SettingsValidator::class)
        ->args([
            '$validator' => service('validator'),
        ]);
    $services->alias(\Jbtronics\SettingsBundle\Manager\SettingsValidatorInterface::class, 'jbtronics.settings.settings_validator');

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

    /**********************************************************************************
     * Storage Adapters
     **********************************************************************************/
    $services->set(\Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter::class);
    $services->set(\Jbtronics\SettingsBundle\Storage\JSONFileStorageAdapter::class)
        ->args([
            '$storageDirectory' => '%kernel.project_dir%/var/jbtronics_settings/',
        ]);
};