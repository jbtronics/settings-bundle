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

namespace Jbtronics\SettingsBundle\DependencyInjection;

use Jbtronics\SettingsBundle\Metadata\Driver\YamlDriver;
use Jbtronics\SettingsBundle\Migrations\SettingsMigrationInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class JbtronicsSettingsExtension extends Extension
{

    public const TAG_PARAMETER_TYPE = 'jbtronics.settings.parameter_type';
    public const TAG_STORAGE_ADAPTER = 'jbtronics.settings.storage_adapter';
    public const TAG_MIGRATION = 'jbtronics.settings.migration';

    public const TAG_INJECTABLE_SETTINGS = 'jbtronics.settings.injectable_settings';

    public const RESSOURCE_TAG_SETTINGS = 'jbtronics.settings';


    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(ParameterTypeInterface::class)
            ->addTag(self::TAG_PARAMETER_TYPE);

        $container->registerForAutoconfiguration(StorageAdapterInterface::class)
            ->addTag(self::TAG_STORAGE_ADAPTER);

        $container->registerForAutoconfiguration(SettingsMigrationInterface::class)
            ->addTag(self::TAG_MIGRATION);

        //Retrieve the configuration for this bundle and process it
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('jbtronics.settings.proxy_dir', $config['proxy_dir']);
        $container->setParameter('jbtronics.settings.proxy_namespace', $config['proxy_namespace']);
        $container->setParameter('jbtronics.settings.search_paths', $config['search_paths']);
        $container->setParameter('jbtronics.settings.yaml_mapping_paths', $config['yaml_mapping_paths']);
        $container->setParameter('jbtronics.settings.default_storage_adapter', $config['default_storage_adapter']);
        $container->setParameter('jbtronics.settings.save_after_migration', $config['save_after_migration']);

        $container->setParameter('jbtronics.settings.file_storage.storage_directory', $config['file_storage']['storage_directory']);
        //The default filename without extension
        $container->setParameter('jbtronics.settings.file_storage.default_filename', $config['file_storage']['default_filename']);

        $container->setParameter('jbtronics.settings.orm.default_entity_class', $config['orm_storage']['default_entity_class']);
        $container->setParameter('jbtronics.settings.orm.prefetch_all', $config['orm_storage']['prefetch_all']);

        $container->setParameter('jbtronics.settings.cache.default_cacheable', $config['cache']['default_cacheable']);
        $container->setAlias('jbtronics.settings.cache.service', $config['cache']['service']);
        $container->setAlias('jbtronics.settings.cache.metadata_service', $config['cache']['metadata_service']);
        $container->setParameter('jbtronics.settings.cache.ttl', $config['cache']['ttl']);
        $container->setParameter('jbtronics.settings.cache.invalidate_on_env_change', $config['cache']['invalidate_on_env_change']);

        // Register YAML-configured settings classes as injectable services
        $this->registerYamlSettingsServices($container, $config['yaml_mapping_paths']);
    }

    /**
     * Eagerly discovers YAML-configured settings classes and registers them as services,
     * so that they can be dependency-injected just like attribute-configured settings.
     */
    private function registerYamlSettingsServices(ContainerBuilder $container, array $yamlMappingPaths): void
    {
        return;

        if (empty($yamlMappingPaths) || !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            return;
        }

        // Use the YamlDriver to discover classes at build time
        $yamlDriver = new YamlDriver($yamlMappingPaths);
        $classNames = $yamlDriver->getAllManagedClassNames();

        foreach ($classNames as $className) {
            $classMetadata = $yamlDriver->loadClassMetadata($className);
            if ($classMetadata === null) {
                continue;
            }

            $dependencyInjectable = $classMetadata->dependencyInjectable;

            // Register the class as a service definition so the compiler pass can configure it
            if (!$container->hasDefinition($className)) {
                $definition = new Definition($className);
                $definition->setAutoconfigured(true);
                $container->setDefinition($className, $definition);
            }

            $definition = $container->getDefinition($className);

            if (method_exists($definition, 'addResourceTag')) { //Symfony 7.3+
                $definition->addResourceTag(self::RESSOURCE_TAG_SETTINGS, [
                    'injectable' => $dependencyInjectable,
                ]);
            } else {
                if ($dependencyInjectable) {
                    $definition->addTag(self::TAG_INJECTABLE_SETTINGS);
                } else {
                    $definition->addTag(ConfigureInjectableSettingsPass::TAG_TO_REMOVE);
                }
            }
        }
    }
}