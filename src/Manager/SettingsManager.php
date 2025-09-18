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

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Exception\SettingsNotValidException;
use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Helper\ProxyClassNameHelper;
use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * This service manages all available settings classes and keeps track of them
 */
final class SettingsManager implements SettingsManagerInterface, ResetInterface
{
    public function __construct(
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsHydratorInterface $settingsHydrator,
        private readonly SettingsResetterInterface $settingsResetter,
        private readonly SettingsValidatorInterface $settingsValidator,
        private readonly SettingsRegistryInterface $settingsRegistry,
        private readonly ProxyFactoryInterface $proxyFactory,
        private readonly EnvVarValueResolverInterface $envVarValueResolver,
        private readonly SettingsClonerInterface $settingsCloner,
    )
    {

    }

    private array $settings_by_class = [];

    public function get(string $settingsClass, bool $lazy = false): object
    {
        //If not a class name is given, try to resolve the name via SettingsRegistry
        if (!class_exists($settingsClass)) {
            $settingsClass = $this->settingsRegistry->getSettingsClassByName($settingsClass);
        }

        //Check if the settings class is already in memory
        if (isset($this->settings_by_class[$settingsClass])) {
            return $this->settings_by_class[$settingsClass];
        }

        //Ensure that the given class is a settings class
        if (!$this->metadataManager->isSettingsClass($settingsClass)) {
            throw new \LogicException(sprintf('The class "%s" is not a settings class. Add the #[Settings] attribute to the class.', $settingsClass));
        }

        //If not create a new instance of the given settings class with the default values
        if (!$lazy) { //If we are not lazy, we initialize the settings class immediately
            $settings = $this->getInitializedVersion($settingsClass);
        } else { //Otherwise we create a lazy loading proxy
            $settings = $this->proxyFactory->createProxy($settingsClass, function (object $instance) use ($settingsClass) {
                $this->getInitializedVersion($settingsClass, $instance);
            });
        }

        //Add it to our memory map
        $this->settings_by_class[$settingsClass] = $settings;
        return $settings;
    }

    /**
     * Initializes a new (or existing) instance of the given settings class with the default values.
     * If $existingInstance is given, the default values are applied to this instance, instead of creating a new one.
     * @param  string  $settingsClass
     * @param  object|null  $existingInstance
     * @return object
     */
    private function getInitializedVersion(string $settingsClass, ?object $existingInstance = null): object
    {
        $metadata = $this->metadataManager->getSettingsMetadata($settingsClass);

        //If we have an existing instance, use the SettingsResetter to initialize it
        if ($existingInstance) {
            $settings = $this->settingsResetter->resetSettings($existingInstance, $metadata);
        } else { //Otherwise create a completely new instance
            $settings = $this->settingsResetter->newInstance($metadata);
        }

        $this->settingsHydrator->hydrate($settings, $metadata);

        //Fill the embedded settings with a lazy loaded instance
        foreach ($this->metadataManager->getSettingsMetadata($settingsClass)->getEmbeddedSettings() as $embeddedSettingsMetadata) {
            $targetClass = $embeddedSettingsMetadata->getTargetClass();
            //Retrieve the embedded settings instance
            $embeddedSettings = $this->get($targetClass, true);

            //Set the embedded settings instance
            PropertyAccessHelper::setProperty($settings, $embeddedSettingsMetadata->getPropertyName(),
                $embeddedSettings);
        }

        return $settings;
    }

    public function reload(string|object $settings, bool $cascade = true): object
    {
        if (is_string($settings)) {
            $settings = $this->get($settings);
        }

        //Reset the settings class to its default values
        $this->resetToDefaultValues($settings);

        //Reload the settings class from the storage adapter
        $this->settingsHydrator->hydrate($settings, $this->metadataManager->getSettingsMetadata($settings));

        //When cascade is enabled, then we also need to reload all embedded settings
        if ($cascade) {
            $settingsClass = ProxyClassNameHelper::resolveEffectiveClass($settings);
            $classes_to_reload = $this->metadataManager->resolveEmbeddedCascade($settingsClass);

            foreach ($classes_to_reload as $class) {
                //Skip the class itself
                if ($settingsClass === $class) {
                    continue;
                }

                //Reload the embedded settings (cascade must be false, otherwise we end up in an endless loop)
                $instance = $this->get($class);
                $this->reload($instance, false);
            }
        }

        return $settings;
    }

    public function save(string|object|array|null $settings = null, bool $cascade = true): void
    {
        //If no class were given, we save all classes
        if ($settings === null) {
            $settings = array_keys($this->settings_by_class);
        }

        if (!is_array($settings)) {
            $settings = [$settings];
        }

        //Iterate over each given settings class to resolve which classes we really need to save
        $classesToSave = [];
        foreach ($settings as $setting) {
            if (is_object($setting)) {
                $setting = ProxyClassNameHelper::resolveEffectiveClass($setting);
            }

            //If we should cascade, we resolve all embedded settings
            if ($cascade) {
                $classesToSave = array_merge($classesToSave, $this->metadataManager->resolveEmbeddedCascade($setting));
            } else { //Or we just add the given class
                $classesToSave[] = $setting;
            }
        }

        //Ensure that the classes are unique
        $classesToSave = array_unique($classesToSave);

        $errors = [];

        //Ensure that the classes are all valid
        foreach ($classesToSave as $class) {
            $instance = $this->get($class, true);

            $errors_per_property = $this->settingsValidator->validate($instance);
            if (count($errors_per_property) > 0) {
                $errors[$class] = $errors_per_property;
            }
        }

        //If there are any errors, throw an exception
        if (count($errors) > 0) {
            throw new SettingsNotValidException($errors);
        }

        //Otherwise persist the settings
        foreach ($classesToSave as $class) {
            $instance = $this->get($class, true);

            //If the settings class is a proxy and was not yet initialized, we do not need to save it as it was not changed
            if (PHP_VERSION_ID >= 80400 && (new \ReflectionClass($instance)->isUninitializedLazyObject($instance))) { //PHP native way
                continue;
            }

            if ($instance instanceof SettingsProxyInterface && $instance instanceof LazyObjectInterface && !$instance->isLazyObjectInitialized()) { //Fallback for older PHP versions
                continue;
            }

            $this->settingsHydrator->persist($instance, $this->metadataManager->getSettingsMetadata($class));
        }
    }

    public function resetToDefaultValues(object|string $settings): void
    {
        if (is_string($settings)) {
            $settings = $this->get($settings);
        }

        //Reset the settings class to its default values
        $this->settingsResetter->resetSettings($settings, $this->metadataManager->getSettingsMetadata($settings));
    }

    public function reset(): void
    {
        //Reset all cached settings classes, to trigger a reload on new requests
        $this->settings_by_class = [];
    }

    public function isEnvVarOverwritten(
        object|string $settings,
        ParameterMetadata|string|\ReflectionProperty $property
    ): bool {
        $settingsMetadata = $this->metadataManager->getSettingsMetadata($settings);

        //Resolve the property to a ParameterMetadata instance
        if ($property instanceof ParameterMetadata) {
            //Ensure that the parameter is part of the settings class
            if ($property->getClassName() !== $settingsMetadata->getClassName()) {
                throw new \InvalidArgumentException(sprintf('The parameter "%s" is not part of the settings class "%s".',
                    $property->getName(), $settingsMetadata->getClassName()));
            }
        } elseif ($property instanceof \ReflectionProperty) {
            //Ensure that the property is part of the settings class
            if ($property->getDeclaringClass()->getName() !== $settingsMetadata->getClassName()) {
                throw new \InvalidArgumentException(sprintf('The property "%s" is not part of the settings class "%s".',
                    $property->getName(), $settingsMetadata->getClassName()));
            }

            $property = $settingsMetadata->getParameterByPropertyName($property->getName());
        } elseif (is_string($property)) {
            $property = $settingsMetadata->getParameterByPropertyName($property);
        }

        //If the property has no env var defined, or its mode is neither OVERWRITE or OVERWRITE_PERSIST, then it is not overwritten
        if ($property->getEnvVar() === null
            || !in_array($property->getEnvVarMode(), [EnvVarMode::OVERWRITE, EnvVarMode::OVERWRITE_PERSIST], true)) {
            return false;
        }

        //Otherwise we assume that the hydrator has overwritten the property, when the env var is set
        return $this->envVarValueResolver->hasValue($property);
    }

    public function createTemporaryCopy(object|string $settings): object
    {
        //We use the metadata to retrieve the effective class name of the settings class
        $metadata = $this->metadataManager->getSettingsMetadata($settings);

        //Retrieve the original settings instance, managed by this service
        $original = $this->get($metadata->getClassName());

        //Use the cloner service to create a temporary copy
        return $this->settingsCloner->createClone($original);
    }

    public function mergeTemporaryCopy(object|string $copy, bool $cascade = true): void
    {
        //We use the metadata to retrieve the effective class name of the settings class
        $metadata = $this->metadataManager->getSettingsMetadata($copy);

        //Retrieve the original settings instance, managed by this service
        $original = $this->get($metadata->getClassName());

        //If the original and the temporary copy are the same, we do not need to merge
        if ($original === $copy) {
            return;
        }

        //Ensure that the copy is valid
        if ($cascade) {
            $errors = $this->settingsValidator->validateRecursively($copy);
            if ($errors) {
                throw new SettingsNotValidException($errors);
            }
        } else {
            $errors = $this->settingsValidator->validate($copy);
            if ($errors) {
                throw SettingsNotValidException::createForSingleClass($original, $errors);
            }
        }


        //Use the cloner service to merge the temporary copy back to the original instance
        $this->settingsCloner->mergeCopy($copy, $original, $cascade);
    }
}