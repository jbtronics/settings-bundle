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

namespace Jbtronics\SettingsBundle\Metadata;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Helper\ProxyClassNameHelper;
use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Symfony\Contracts\Cache\CacheInterface;

final class MetadataManager implements MetadataManagerInterface
{
    private array $metadata_cache = [];

    private array $resolve_embedded_cache = [];

    private const CACHE_KEY_PREFIX = 'jbtronics_settings.metadata.';

    private const CACHE_KEY_EMBEDDED_PREFIX = 'jbtronics_settings.embedded.';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly bool $debug_mode,
        private readonly SettingsRegistryInterface $settingsRegistry,
        private readonly ParameterTypeGuesserInterface $parameterTypeGuesser,
        private readonly ?string $defaultStorageAdapter = null,
    ) {
    }

    public function isSettingsClass(string|object $className): bool
    {
        //If the given name is not a class name, try to resolve the name via SettingsRegistry
        if (!class_exists($className)) {
            $className = $this->settingsRegistry->getSettingsClassByName($className);
        }

        //Check if the given class contains a #[ConfigClass] attribute.
        //If yes, return true, otherwise return false.

        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        return count($attributes) > 0;
    }

    public function getSettingsMetadata(string|object $className): SettingsMetadata
    {
        if (is_object($className)) {
            $className = ProxyClassNameHelper::resolveEffectiveClass($className);
        } elseif (!class_exists($className)) { //If the given name is not a class name, try to resolve the name via SettingsRegistry
            $className = $this->settingsRegistry->getSettingsClassByName($className);
        }

        //Check if the metadata for the given class is already cached.
        if (isset($this->metadata_cache[$className])) {
            return $this->metadata_cache[$className];
        }

        if ($this->debug_mode) {
            $metadata = $this->getMetadataUncached($className);
        } else {
            $metadata = $this->cache->get(self::CACHE_KEY_PREFIX.md5($className), function () use ($className) {
                return $this->getMetadataUncached($className);
            });
        }

        $this->metadata_cache[$className] = $metadata;
        return $metadata;
    }

    private function getMetadataUncached(string $className): SettingsMetadata
    {
        //Retrieve the #[ConfigClass] attribute from the given class.
        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        if (count($attributes) < 1) {
            throw new \LogicException(sprintf('The class "%s" is not a config class. Add the #[ConfigClass] attribute to the class.',
                $className));
        }
        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The class "%s" has more than one ConfigClass atrributes! Only one is allowed',
                $className));
        }

        /** @var Settings $classAttribute */
        $classAttribute = $attributes[0]->newInstance();
        $parameters = [];
        $embeddeds = [];


        //Retrieve all parameter attributes on the properties of the given class
        $reflProperties = PropertyAccessHelper::getProperties($className);
        foreach ($reflProperties as $reflProperty) {

            //Try to parse the parameter metadata from the property
            $parameterMetadata = $this->parseParameterMetadata($className, $reflProperty, $classAttribute);
            if ($parameterMetadata !== null) {
                $parameters[] = $parameterMetadata;
            }

            //Try to parse the embedded metadata from the property
            $embeddedMetadata = $this->parseEmbeddedMetadata($className, $reflProperty, $classAttribute);
            if ($embeddedMetadata !== null) {
                $embeddeds[] = $embeddedMetadata;
            }
        }

        //Ensure that the settings version is greather than 0
        if ($classAttribute->version !== null && $classAttribute->version <= 0) {
            throw new \LogicException(sprintf("The version of the settings class %s must be greater than zero! %d given", $className, $classAttribute->version));
        }

        //Now we have all infos required to build our settings metadata
        return new SettingsMetadata(
            className: $className,
            parameterMetadata: $parameters,
            storageAdapter: $classAttribute->storageAdapter ?? $this->defaultStorageAdapter
                ?? throw new \LogicException(sprintf('No storage adapter set for the settings class "%s". Please set one explicitly or define a default one in the bundle config!',
                $className)),
            name: $classAttribute->name ?? SettingsRegistry::generateDefaultNameFromClassName($className),
            defaultGroups: $classAttribute->groups,
            version: $classAttribute->version,
            migrationService: $classAttribute->migrationService,
            storageAdapterOptions: $classAttribute->storageAdapterOptions,
            embeddedMetadata: $embeddeds,
        );
    }

    /**
     * Tries to parse the embedded metadata from the given property. If the property is not an embedded settings, null is returned.
     * @param  string  $className
     * @param  \ReflectionProperty  $reflProperty
     * @param  Settings  $classAttribute
     * @return EmbeddedSettingsMetadata|null
     */
    private function parseEmbeddedMetadata(string $className, \ReflectionProperty $reflProperty, Settings $classAttribute): ?EmbeddedSettingsMetadata
    {
        $attributes = $reflProperty->getAttributes(EmbeddedSettings::class);
        //Skip properties without a EmbeddedSettings attribute
        if (count($attributes) < 1) {
            return null;
        }

        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The property "%s" of the class "%s" has more than one EmbeddedSettings attributes! Only one is allowed',
                $reflProperty->getName(), $className));
        }

        //Create a new instance of the attribute
        /** @var EmbeddedSettings $attribute */
        $attribute = $attributes[0]->newInstance();

        //If the target class is not set explicitly, try to guess it from the property type
        $targetClass = $attribute->target;
        if ($targetClass === null) {
            $type = $reflProperty->getType();
            if (!$type instanceof \ReflectionNamedType) {
                throw new \LogicException(sprintf('The property "%s" of the class "%s" has no usable type declaration and the target class could not be guessed. Please set the target class explicitly!',
                    $reflProperty->getName(), $className));
            }
            //Assume the type is our target class
            $targetClass = $type->getName();
        }

        //Create the embedded metadata
        return new EmbeddedSettingsMetadata(
            className: $className,
            propertyName: $reflProperty->getName(),
            targetClass: $targetClass,
            groups: $attribute->groups ?? $classAttribute->groups ?? [],
        );
    }

    /**
     * Tries to parse the parameter metadata from the given property. If the property is not a settings parameter, null is returned.
     * @param  string  $className
     * @param  \ReflectionProperty  $reflProperty
     * @param  Settings  $classAttribute
     * @return ParameterMetadata|null
     */
    private function parseParameterMetadata(string $className, \ReflectionProperty $reflProperty, Settings $classAttribute): ?ParameterMetadata
    {
        $attributes = $reflProperty->getAttributes(SettingsParameter::class);
        //Skip properties without a SettingsParameter attribute
        if (count($attributes) < 1) {
            return null;
        }
        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The property "%s" of the class "%s" has more than one ConfigEntry attributes! Only one is allowed',
                $reflProperty->getName(), $className));
        }

        //Add it to our list
        /** @var SettingsParameter $attribute */
        $attribute = $attributes[0]->newInstance();

        //Try to guess type
        $type = $attribute->type ?? $this->parameterTypeGuesser->guessParameterType($reflProperty);
        if ($type === null) {
            throw new \LogicException(sprintf('The property "%s" of the class "%s" has no type set and the type could not be guessed. Please set the type explicitly!',
                $reflProperty->getName(), $className));
        }

        //Try to guess extra options
        $options = array_merge($this->parameterTypeGuesser->guessOptions($reflProperty) ?? [], $attribute->options);

        //Try to determine whether the property is nullable
        $nullable = $attribute->nullable;
        if ($nullable === null) {
            //Check if the type is nullable
            $nullable = $reflProperty->getType()?->allowsNull();
            //If the type is not declared then nullable is still null. Throw an exception then
            if ($nullable === null) {
                throw new \LogicException(sprintf('The property "%s" of the class "%s" has no type declaration and the nullable flag could not be guessed. Please set the nullable flag explicitly!',
                    $reflProperty->getName(), $className));
            }
        }

        return new ParameterMetadata(
            className: $className,
            propertyName: $reflProperty->getName(),
            type: $attribute->type ?? $type,
            nullable: $nullable,
            name: $attribute->name,
            label: $attribute->label,
            description: $attribute->description,
            options: $options,
            formType: $attribute->formType,
            formOptions: $attribute->formOptions,
            groups: $attribute->groups ?? $classAttribute->groups ?? [],
        );
    }

    public function resolveEmbeddedCascade(string $className): array
    {
        //Check if the embedded cascade for the given class is already cached.
        if (isset($this->resolve_embedded_cache[$className])) {
            return $this->resolve_embedded_cache[$className];
        }

        if ($this->debug_mode) {
            $embeddedCascade = $this->resolveEmbeddedCascadeUncached($className);
        } else {
            $embeddedCascade = $this->cache->get(self::CACHE_KEY_EMBEDDED_PREFIX.md5($className), function () use ($className) {
                return $this->resolveEmbeddedCascadeUncached($className);
            });
        }

        $this->resolve_embedded_cache[$className] = $embeddedCascade;
        return $embeddedCascade;
    }

    private function resolveEmbeddedCascadeUncached(string $className, ?array $done_classes = null): array
    {
        //Prefill the list with the className
        $done_classes = $done_classes ?? [$className];

        //Retrieve the metadata of the classname
        $metadata = $this->getSettingsMetadata($className);

        //Retrieve all embedded settings
        $embeddedSettings = $metadata->getEmbeddedSettings();

        //Iterate over all embedded settings and add them to our list
        foreach ($embeddedSettings as $embeddedSetting) {
            $targetClass = $embeddedSetting->getTargetClass();

            //If the target class is already in the list, skip it
            if (in_array($targetClass, $done_classes, true)) {
                continue;
            }

            //Add the target class to the list
            $done_classes[] = $targetClass;

            //Resolve the embedded cascade of the target class
            $done_classes = array_merge($done_classes, $this->resolveEmbeddedCascadeUncached($targetClass, $done_classes));
        }

        return array_unique($done_classes);
    }
}