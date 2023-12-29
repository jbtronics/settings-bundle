<?php

namespace Jbtronics\SettingsBundle\DependencyInjection;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SettingsExtension extends Extension
{

    public const TAG_PARAMETER_TYPE = 'jbtronics.settings.parameter_type';
    public const TAG_STORAGE_ADAPTER = 'jbtronics.settings.storage_adapter';


    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(ParameterTypeInterface::class)
            ->addTag(self::TAG_PARAMETER_TYPE);

        $container->registerForAutoconfiguration(StorageAdapterInterface::class)
            ->addTag(self::TAG_STORAGE_ADAPTER);
    }
}