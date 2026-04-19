<?php

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\DependencyInjection;

use Jbtronics\SettingsBundle\Metadata\Driver\CompileTimeMetadataDriverInterface;
use Jbtronics\SettingsBundle\Metadata\Driver\MetadataDriverInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * This compiler pass is responsible for processing the metadata provided by compile-time metadata providers and tagging the corresponding service definitions accordingly.
 * It ensures that the classes provided by the metadata providers are registered as services and tagged with the appropriate tags for further processing by other compiler passes.
 *
 * This only handles compile time metadata providers. The registration of the attributes is donne in JbtronicsSettingsBundle, as it has to happen before the compiler pass runs.
 * @internal
 */
final class TagSettingsPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {

        $containerParams = $container->getParameterBag()->all();

        $metadata_compiler_providers = $containerParams['jbtronics.settings.metadata_compiler_providers'] ?? [];

        foreach ($metadata_compiler_providers as $providerClass) {
            if (!class_exists($providerClass)) {
                throw new LogicException('Metadata compiler provider class "' . $providerClass . '" does not exist. Please check your configuration.');
            }
            if (!is_a($providerClass, CompileTimeMetadataDriverInterface::class, true)) {
                throw new LogicException('Metadata compiler provider class "' . $providerClass . '" must implement ' . MetadataDriverInterface::class . '. Please check your configuration.');
            }


            $compileTimeMetadata = $providerClass::getServiceMetadataForContainerCompilation($containerParams);

            foreach ($compileTimeMetadata as $className => $classMetadata) {
                $dependencyInjectable = $classMetadata->dependencyInjectable;

                // Register the class as a service definition so the compiler pass can configure it
                if (!$container->hasDefinition($className)) {
                    $definition = new Definition($className);
                    $definition->setAutoconfigured(true);
                    $container->setDefinition($className, $definition);
                }

                $definition = $container->getDefinition($className);

                if (method_exists($definition, 'addResourceTag')) { //Symfony 7.3+
                    $definition->addResourceTag(JbtronicsSettingsExtension::RESSOURCE_TAG_SETTINGS, [
                        'injectable' => $dependencyInjectable,
                    ]);
                } else {
                    if ($dependencyInjectable) {
                        $definition->addTag(JbtronicsSettingsExtension::TAG_INJECTABLE_SETTINGS);
                    } else {
                        $definition->addTag(ConfigureInjectableSettingsPass::TAG_TO_REMOVE);
                    }
                }
            }
        }
    }
}