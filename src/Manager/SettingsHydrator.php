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

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Migrations\MigrationsManager;
use Jbtronics\SettingsBundle\Migrations\MigrationsManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;

class SettingsHydrator implements SettingsHydratorInterface
{
    /** @var string The name of the key used to store meta information in persisted data */
    public const META_KEY = '$META$';

    public function __construct(
        private readonly StorageAdapterRegistryInterface $storageAdapterRegistry,
        private readonly ParameterTypeRegistryInterface $parameterTypeRegistry,
        private readonly MigrationsManagerInterface $migrationsManager,
        private readonly EnvVarValueResolverInterface $envVarValueResolver,
        private readonly bool $saveAfterMigration = true,
    ) {

    }

    public function hydrate(object $settings, SettingsMetadata $metadata): object
    {
        //Retrieve the storage adapter for the given settings object.
        /** @var StorageAdapterInterface $storageAdapter */
        $storageAdapter = $this->storageAdapterRegistry->getStorageAdapter($metadata->getStorageAdapter());

        //Retrieve the normalized representation of the settings object from the storage adapter.
        $normalizedRepresentation = $storageAdapter->load($metadata->getStorageKey(), $metadata->getStorageAdapterOptions());

        //If the normalized representation is null, the settings object has not been persisted yet, we can return it as is.
        if ($normalizedRepresentation === null) {
            return $settings;
        }

        $migrated = false;

        //Migrate to the latest version if necessary.
        if ($this->migrationsManager->requireUpgrade($metadata, $normalizedRepresentation)) {
            $normalizedRepresentation = $this->migrationsManager->performUpgrade($metadata, $normalizedRepresentation);
            $migrated = true;
        }

        //Apply the normalized representation to the settings object.
        $tmp = $this->applyNormalizedRepresentation($normalizedRepresentation, $settings, $metadata);

        //Apply all environment variable overwrites to the settings object.
        $this->applyEnvVariableOverwrites($tmp, $metadata);

        //If we went here, then the application of the normalized representation was successful.
        //If the settings object was migrated, we save it to the storage adapter, to make later retrievals faster.
        if ($this->saveAfterMigration && $migrated) {
            $storageAdapter->save($metadata->getStorageKey(), $normalizedRepresentation);
        }

        return $tmp;
    }

    public function persist(object $settings, SettingsMetadata $metadata): object
    {
        //Retrieve the storage adapter for the given settings object.
        /** @var StorageAdapterInterface $storageAdapter */
        $storageAdapter = $this->storageAdapterRegistry->getStorageAdapter($metadata->getStorageAdapter());

        //Generate a normalized representation of the settings object.
        $normalizedRepresentation = $this->toNormalizedRepresentation($settings, $metadata);

        //Undo the eventual environment variable overwrites for the normalized representation.
        //The null value for the old data will trigger the loading from the storage adapter, if required.
        $normalizedRepresentation = $this->undoEnvVariableOverwrites($metadata, $normalizedRepresentation, null);

        //Persist the normalized representation to the storage adapter.
        $storageAdapter->save($metadata->getStorageKey(), $normalizedRepresentation, $metadata->getStorageAdapterOptions());

        //Return the settings object.
        return $settings;
    }

    /**
     * Converts the given settings object to a normalized representation using the metadata from the given metadata.
     * @param  object  $settings
     * @param  SettingsMetadata  $metadata
     * @return array
     */
    public function toNormalizedRepresentation(object $settings, SettingsMetadata $metadata): array
    {
        //Ensure that the metadata is compatible with the given settings object.
        if (!is_a($settings, $metadata->getClassName())) {
            throw new \LogicException(sprintf('The given settings object is not compatible with the metadata. Expected "%s", got "%s"', $metadata->getClassName(), get_class($settings)));
        }

        $normalizedRepresentation = [];

        foreach ($metadata->getParameters() as $parameterMetadata) {
            $parameterName = $parameterMetadata->getName();

            $value = PropertyAccessHelper::getProperty($settings, $parameterMetadata->getPropertyName());

            /** @var ParameterTypeInterface $converter */
            $converter = $this->parameterTypeRegistry->getParameterType($parameterMetadata->getType());

            $normalizedRepresentation[$parameterName] = $converter->convertPHPToNormalized($value, $parameterMetadata);
        }

        //Add the version meta tag to the normalized representation, if needed
        if ($metadata->isVersioned()) {
            $normalizedRepresentation[self::META_KEY][MigrationsManager::META_VERSION_KEY] = $metadata->getVersion();
        }


        return $normalizedRepresentation;
    }

    /**
     * Apply the given normalized representation to the given settings object using the given metadata .
     * @param  array  $normalizedRepresentation
     * @param  object  $settings
     * @param  SettingsMetadata  $metadata
     * @return object
     */
    public function applyNormalizedRepresentation(array $normalizedRepresentation, object $settings, SettingsMetadata $metadata): object
    {
        //Ensure that the metadata is compatible with the given settings object.
        if (!is_a($settings, $metadata->getClassName())) {
            throw new \LogicException(sprintf('The given settings object is not compatible with the metadata. Expected "%s", got "%s"', $metadata->getClassName(), get_class($settings)));
        }

        foreach ($metadata->getParameters() as $parameterMetadata) {
            $parameterName = $parameterMetadata->getName();

            //Skip parameters which are not present in the normalized representation.
            if (!array_key_exists($parameterName, $normalizedRepresentation)) {
                continue;
            }

            $normalized = $normalizedRepresentation[$parameterName];

            /** @var ParameterTypeInterface $converter */
            $converter = $this->parameterTypeRegistry->getParameterType($parameterMetadata->getType());

            $value = $converter->convertNormalizedToPHP($normalized, $parameterMetadata);

            PropertyAccessHelper::setProperty($settings, $parameterMetadata->getPropertyName(), $value);
        }

        return $settings;
    }

    /**
     * Apply the overwrite mode environment variables defined in the parameter metadata to the given settings object.
     * This means that all parameters with an environment variable defined will be set to the value of the environment
     * variable.
     * @param  object  $settings
     * @param  SettingsMetadata  $metadata
     * @return object
     */
    public function applyEnvVariableOverwrites(object $settings, SettingsMetadata $metadata): object
    {
        foreach ($metadata->getParametersWithEnvVar([EnvVarMode::OVERWRITE, EnvVarMode::OVERWRITE_PERSIST]) as $parameterMetadata) {
            //Skip the parameter, if the environment variable is not set
            if (!$this->envVarValueResolver->hasValue($parameterMetadata)) {
                continue;
            }

            //Retrieve the value from the environment variable
            $value = $this->envVarValueResolver->getValue($parameterMetadata);

            //Set the value to the property
            PropertyAccessHelper::setProperty($settings, $parameterMetadata->getPropertyName(), $value);
        }

        return $settings;
    }

    /**
     * Undo the environment variable overwrites for the normalized representation of the settings object using the given
     * old data and metadata.
     * If the environment variable is existing and the envVarMode is overwritten (not overwrite_persist), the value will
     * normalize data of this parameter will be reset to the value from the old data.
     * If oldData is null, the old data will be retrieved from the storage adapter.
     * @param  SettingsMetadata  $metadata
     * @param  array  $normalizedData
     * @param  ?array  $oldData
     * @return array
     */
    public function undoEnvVariableOverwrites(SettingsMetadata $metadata, array $normalizedData, ?array $oldData): array
    {
        //Check for every overwritten parameter, if the environment variable is set and the mode is overwritten (not persist)
        foreach ($metadata->getParametersWithEnvVar(EnvVarMode::OVERWRITE) as $parameterMetadata) {
            //Skip if the environment variable is not set, and therefore the parameter was not overwritten
            if (!$this->envVarValueResolver->hasValue($parameterMetadata)) {
                continue;
            }

            //If we go here, we require the old data to reset the parameter. Load it from storage if not given.
            if ($oldData === null) {
                $storageAdapter = $this->storageAdapterRegistry->getStorageAdapter($metadata->getStorageAdapter());
                //Retrieve the currently saved old data from the storage adapter
                $oldData = $storageAdapter->load($metadata->getStorageKey(), $metadata->getStorageAdapterOptions());
            }

            $parameterName = $parameterMetadata->getName();

            //Check if the parameter is present in the old data. Then unset it in the normalized data.
            if ($oldData === null || !array_key_exists($parameterName, $oldData)) {
                unset($normalizedData[$parameterName]);
            } else { //Otherwise set the old data to the normalized data
                $normalizedData[$parameterName] = $oldData[$parameterName];
            }
        }

        return $normalizedData;
    }
}