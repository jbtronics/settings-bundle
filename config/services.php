<?php


/*
 * Copyright (c) 2024 Jan Böhmer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use Jbtronics\SettingsBundle\DependencyInjection\JbtronicsSettingsExtension;
use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Manager\SettingsResetter;
use Jbtronics\SettingsBundle\Manager\SettingsResetterInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManager;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Migrations\SettingsMigrationInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistry;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistry;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()->private()
        ->instanceof(ParameterTypeInterface::class)->tag(JbtronicsSettingsExtension::TAG_PARAMETER_TYPE)
        ->instanceof(StorageAdapterInterface::class)->tag(JbtronicsSettingsExtension::TAG_STORAGE_ADAPTER)
        ->instanceof(SettingsMigrationInterface::class)->tag(JbtronicsSettingsExtension::TAG_MIGRATION)
        ;

    //Inject the parameter type registry into all SettingsMigration services
    $services->instanceof(\Jbtronics\SettingsBundle\Migrations\SettingsMigration::class)
        ->call('setParameterTypeRegistry', [service('jbtronics.settings.parameter_type_registry')]);

    $services->set('jbtronics.settings.settings_registry', SettingsRegistry::class)
        ->args([
            '$directories' => '%jbtronics.settings.search_paths%',
            '$cache' => service('cache.app'),
            '$debug_mode' => '%kernel.debug%',
        ])
    ;
    $services->alias(SettingsRegistryInterface::class, 'jbtronics.settings.settings_registry');

    $services->set('jbtronics.settings.metadata_manager', MetadataManager::class)
        ->args([
            '$cache' => service('cache.app'),
            '$debug_mode' => '%kernel.debug%',
            '$settingsRegistry' => service('jbtronics.settings.settings_registry'),
            '$parameterTypeGuesser' => service('jbtronics.settings.parameter_type_guesser'),
            '$defaultStorageAdapter' => '%jbtronics.settings.default_storage_adapter%'
        ])
    ;
    $services->alias(MetadataManagerInterface::class, 'jbtronics.settings.metadata_manager');

    $services->set('jbtronics.settings.metadata_cache_warmer', \Jbtronics\SettingsBundle\CacheWarmer\MetadataCacheWarmer::class)
        ->args([
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
            '$settingsRegistry' => service('jbtronics.settings.settings_registry'),
        ])
        ->tag('kernel.cache_warmer');

    $services->set('jbtronics.settings.settings_manager', SettingsManager::class)
        ->args([
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
            '$settingsHydrator' => service('jbtronics.settings.settings_hydrator'),
            '$settingsResetter' => service('jbtronics.settings.settings_resetter'),
            '$settingsValidator' => service('jbtronics.settings.settings_validator'),
            '$settingsRegistry' => service('jbtronics.settings.settings_registry'),
            '$proxyFactory' => service('jbtronics.settings.proxy_factory'),
        ])
        ;
    $services->alias(SettingsManagerInterface::class, 'jbtronics.settings.settings_manager');

    $services->set('jbtronics.settings.parameter_type_registry', ParameterTypeRegistry::class)
        ->args([
            '$locator' => tagged_locator(JbtronicsSettingsExtension::TAG_PARAMETER_TYPE)
        ])
        ;
    $services->alias(ParameterTypeRegistryInterface::class, 'jbtronics.settings.parameter_type_registry');

    $services->set('jbtronics.settings.storage_adapter_registry', StorageAdapterRegistry::class)
        ->args([
            '$locator' => tagged_locator(JbtronicsSettingsExtension::TAG_STORAGE_ADAPTER)
        ])
        ;
    $services->alias(StorageAdapterRegistryInterface::class, 'jbtronics.settings.storage_adapter_registry');

    $services->set('jbtronics.settings.settings_hydrator', SettingsHydrator::class)
        ->args([
            '$storageAdapterRegistry' => service('jbtronics.settings.storage_adapter_registry'),
            '$parameterTypeRegistry' => service('jbtronics.settings.parameter_type_registry'),
            '$migrationsManager' => service('jbtronics.settings.settings_migration_manager'),
            '$saveAfterMigration' => '%jbtronics.settings.save_after_migration%',
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

    $services->set('jbtronics.settings.parameter_type_guesser', \Jbtronics\SettingsBundle\Metadata\ParameterTypeGuesser::class)
        ;
    $services->alias(\Jbtronics\SettingsBundle\Metadata\ParameterTypeGuesserInterface::class, 'jbtronics.settings.parameter_type_guesser');

    $services->set('jbtronics.settings.profiler_data_collector', SettingsCollector::class)
        ->tag('data_collector')
        ->args([
            '$configurationRegistry' => service('jbtronics.settings.settings_registry'),
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
            '$settingsManager' => service('jbtronics.settings.settings_manager'),
        ]);

    //Only register the twig extension if twig is available
    if (interface_exists(\Twig\Extension\ExtensionInterface::class)) {
        $services->set('jbtronics.settings.twig_extension', \Jbtronics\SettingsBundle\Twig\SettingsExtension::class)
            ->tag('twig.extension')
            ->args([
                '$settingsManager' => service('jbtronics.settings.settings_manager'),
            ]);
    }

    $services->set('jbtronics.settings.env_processor', \Jbtronics\SettingsBundle\DependencyInjection\SettingsEnvProcessor::class)
        ->tag('container.env_var_processor')
        ->args([
            '$settingsManager' => service('jbtronics.settings.settings_manager'),
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
        ]);

    /*********************************************************************************
     * Lazy loading subsystem
     ********************************************************************************/

    $services->set('jbtronics.settings.proxy_factory', \Jbtronics\SettingsBundle\Proxy\ProxyFactory::class)
        ->public() //Must be public, as we use it from inside the SettingsBundle class
        ->args([
            '$proxyDir' => '%jbtronics.settings.proxy_dir%',
            '$proxyNamespace' => '%jbtronics.settings.proxy_namespace%',
        ]);
    $services->alias(\Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface::class, 'jbtronics.settings.proxy_factory');

    $services->set('jbtronics.settings.proxy_cache_warmer', \Jbtronics\SettingsBundle\CacheWarmer\ProxyCacheWarmer::class)
        ->args([
            '$settingsRegistry' => service('jbtronics.settings.settings_registry'),
            '$proxyFactory' => service('jbtronics.settings.proxy_factory'),
        ])
        ->tag('kernel.cache_warmer');

    /*********************************************************************************
     * Form subsystem
     *********************************************************************************/

    $services->set('jbtronics.settings.settings_form_builder', \Jbtronics\SettingsBundle\Form\SettingsFormBuilder::class)
        ->args([
            '$parameterTypeRegistry' => service('jbtronics.settings.parameter_type_registry'),
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
        ]);
    $services->alias(\Jbtronics\SettingsBundle\Form\SettingsFormBuilderInterface::class, 'jbtronics.settings.settings_form_builder');

    $services->set('jbtronics.settings.settings_form_factory', \Jbtronics\SettingsBundle\Form\SettingsFormFactory::class)
        ->args([
            '$settingsManager' => service('jbtronics.settings.settings_manager'),
            '$metadataManager' => service('jbtronics.settings.metadata_manager'),
            '$settingsFormBuilder' => service('jbtronics.settings.settings_form_builder'),
            '$formFactory' => service('form.factory'),
        ]);
    $services->alias(\Jbtronics\SettingsBundle\Form\SettingsFormFactoryInterface::class, 'jbtronics.settings.settings_form_factory');

    /*************************************************************************************
     * Migrations subsystem
     *************************************************************************************/

    $services->set('jbtronics.settings.settings_migration_manager', \Jbtronics\SettingsBundle\Migrations\MigrationsManager::class)
        ->args([
            '$locator' => tagged_locator(JbtronicsSettingsExtension::TAG_MIGRATION),
        ]);
    $services->alias(\Jbtronics\SettingsBundle\Migrations\MigrationsManagerInterface::class, 'jbtronics.settings.settings_migration_manager');

    /**********************************************************************************
     * Parameter Types
     **********************************************************************************/
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\IntType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\StringType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\BoolType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\FloatType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\EnumType::class);
    $services->set(\Jbtronics\SettingsBundle\ParameterTypes\DatetimeType::class);

    /**********************************************************************************
     * Storage Adapters
     **********************************************************************************/
    $services->set(\Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter::class);
    $services->set(\Jbtronics\SettingsBundle\Storage\JSONFileStorageAdapter::class)
        ->args([
            '$storageDirectory' => '%jbtronics.settings.file_storage.storage_directory%',
            '$defaultFilename' => '%jbtronics.settings.file_storage.default_filename%.json',
        ]);
    $services->set(\Jbtronics\SettingsBundle\Storage\PHPFileStorageAdapter::class)
        ->args([
            '$storageDirectory' => '%jbtronics.settings.file_storage.storage_directory%',
            '$defaultFilename' => '%jbtronics.settings.file_storage.default_filename%.php',
        ]);
};