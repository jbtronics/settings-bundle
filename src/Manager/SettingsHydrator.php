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
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;

class SettingsHydrator implements SettingsHydratorInterface
{
    public function __construct(
        private readonly StorageAdapterRegistryInterface $storageAdapterRegistry,
        private readonly ParameterTypeRegistryInterface $parameterTypeRegistry,
    ) {

    }

    public function hydrate(object $settings, SettingsSchema $schema): object
    {
        //Retrieve the storage adapter for the given settings object.
        /** @var StorageAdapterInterface $storageAdapter */
        $storageAdapter = $this->storageAdapterRegistry->getStorageAdapter($schema->getStorageAdapter());

        //Retrieve the normalized representation of the settings object from the storage adapter.
        $normalizedRepresentation = $storageAdapter->load($schema->getStorageKey());

        //If the normalized representation is null, the settings object has not been persisted yet, we can return it as is.
        if ($normalizedRepresentation === null) {
            return $settings;
        }

        //Apply the normalized representation to the settings object.
        return $this->applyNormalizedRepresentation($normalizedRepresentation, $settings, $schema);
    }

    public function persist(object $settings, SettingsSchema $schema): object
    {
        //Retrieve the storage adapter for the given settings object.
        /** @var StorageAdapterInterface $storageAdapter */
        $storageAdapter = $this->storageAdapterRegistry->getStorageAdapter($schema->getStorageAdapter());

        //Generate a normalized representation of the settings object.
        $normalizedRepresentation = $this->toNormalizedRepresentation($settings, $schema);

        //Persist the normalized representation to the storage adapter.
        $storageAdapter->save($schema->getStorageKey(), $normalizedRepresentation);

        //Return the settings object.
        return $settings;
    }

    /**
     * Converts the given settings object to a normalized representation using the metadata from the given schema.
     * @param  object  $settings
     * @param  SettingsSchema  $schema
     * @return array
     */
    public function toNormalizedRepresentation(object $settings, SettingsSchema $schema): array
    {
        //Ensure that the schema is compatible with the given settings object.
        if (!is_a($settings, $schema->getClassName())) {
            throw new \LogicException(sprintf('The given settings object is not compatible with the schema. Expected "%s", got "%s"', $schema->getClassName(), get_class($settings)));
        }

        $normalizedRepresentation = [];

        foreach ($schema->getParameters() as $parameterSchema) {
            $parameterName = $parameterSchema->getName();

            $value = PropertyAccessHelper::getProperty($settings, $parameterSchema->getPropertyName());

            /** @var ParameterTypeInterface $converter */
            $converter = $this->parameterTypeRegistry->getParameterType($parameterSchema->getType());

            $normalizedRepresentation[$parameterName] = $converter->convertPHPToNormalized($value, $schema, $parameterName);
        }

        return $normalizedRepresentation;
    }

    /**
     * Apply the given normalized representation to the given settings object using the metadata from the given schema.
     * @param  array  $normalizedRepresentation
     * @param  object  $settings
     * @param  SettingsSchema  $schema
     * @return object
     */
    public function applyNormalizedRepresentation(array $normalizedRepresentation, object $settings, SettingsSchema $schema): object
    {
        //Ensure that the schema is compatible with the given settings object.
        if (!is_a($settings, $schema->getClassName())) {
            throw new \LogicException(sprintf('The given settings object is not compatible with the schema. Expected "%s", got "%s"', $schema->getClassName(), get_class($settings)));
        }

        foreach ($schema->getParameters() as $parameterSchema) {
            $parameterName = $parameterSchema->getName();

            //Skip parameters which are not present in the normalized representation.
            if (!isset($normalizedRepresentation[$parameterName])) {
                continue;
            }

            $normalized = $normalizedRepresentation[$parameterName];

            /** @var ParameterTypeInterface $converter */
            $converter = $this->parameterTypeRegistry->getParameterType($parameterSchema->getType());

            $value = $converter->convertNormalizedToPHP($normalized, $schema, $parameterName);

            PropertyAccessHelper::setProperty($settings, $parameterSchema->getPropertyName(), $value);
        }

        return $settings;
    }
}