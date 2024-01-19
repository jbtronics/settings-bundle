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

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;

/**
 * This class represents the metadata (structure) of a settings class
 * @template T of object
 */
class SettingsMetadata
{
    /**
     * @var ParameterMetadata[]
     * @phpstan-var array<string, ParameterMetadata>
     */
    private readonly array $parametersByPropertyNames;

    /**
     * @var ParameterMetadata[]
     * @phpstan-var array<string, ParameterMetadata>
     */
    private readonly array $parametersByName;

    /**
     * @var ParameterMetadata[][]
     * @phpstan-var array<string, ParameterMetadata[]>
     */
    private readonly array $parametersByGroups;

    /**
     * Create a new settings metadata instance
     * @param  string  $className The class name of the settings class.
     * @phpstan-param  class-string<T>  $className
     * @param  ParameterMetadata[]  $parameterMetadata The parameter metadata of the settings class.
     * @param  string  $storageAdapter The storage adapter, which should be used to store the settings of this class.
     * @phpstan-param class-string<StorageAdapterInterface> $storageAdapter
     * @param  string  $name The (short) name of the settings class.
     * @param  string[]  $defaultGroups The default groups, which the parameters of this settings class should belong too, if they are not explicitly set.
     */
    public function __construct(
        private readonly string $className,
        array $parameterMetadata,
        private readonly string $storageAdapter,
        private readonly string $name,
        private readonly ?array $defaultGroups = null,
    )
    {
        //Sort the parameters by their property names and names
        $byName = [];
        $byPropertyName = [];
        $byGroups = [];

        foreach ($parameterMetadata as $parameterMetadatum) {
            $byName[$parameterMetadatum->getName()] = $parameterMetadatum;
            $byPropertyName[$parameterMetadatum->getPropertyName()] = $parameterMetadatum;

            //Add the parameter to the groups it belongs to
            foreach ($parameterMetadatum->getGroups() as $group) {
                $byGroups[$group][] = $parameterMetadatum;
            }
        }

        $this->parametersByName = $byName;
        $this->parametersByPropertyNames = $byPropertyName;
        $this->parametersByGroups = $byGroups;
    }

    /**
     * Returns the class name of the configuration class, which is managed by this metadata.
     * @return string
     * @phpstan-return class-string<T>
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the storage key, which is used to store the settings of this class.
     * @return string
     */
    public function getStorageKey(): string
    {
        return $this->getName();
    }

    /**
     * Returns the service id of the storage adapter, which should be used to store the settings of this class.
     * @return string
     * @phpstan-return class-string<StorageAdapterInterface> $className
     */
    public function getStorageAdapter(): string
    {
        return $this->storageAdapter;
    }

    /**
     * Retrieve all parameter metadata of this settings class in the form of an associative array, where the key is the
     * parameter name (not necessarily the property name) and the value is the parameter metadata.
     * @return array<string, ParameterMetadata>
     */
    public function getParameters(): array
    {
        return $this->parametersByName;
    }

    /**
     * Retrieve the parameter metadata of the parameter with the given name (not necessarily the property name)
     * @param  string  $name
     * @return ParameterMetadata
     */
    public function getParameter(string $name): ParameterMetadata
    {
        return $this->parametersByName[$name] ?? throw new \InvalidArgumentException(sprintf('The parameter "%s" does not exist in the settings class "%s"', $name, $this->className));
    }

    /**
     * Check if a parameter with the given name (not necessarily the property name) exists in this settings class.
     * @param  string  $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parametersByName[$name]);
    }

    /**
     * Retrieve the parameter metadata of the parameter with the given property name (not necessarily the name).
     * @param  string  $name
     * @return ParameterMetadata
     */
    public function getParameterByPropertyName(string $name): ParameterMetadata
    {
        return $this->parametersByPropertyNames[$name] ?? throw new \InvalidArgumentException(sprintf('The parameter with the property name "%s" does not exist in the settings class "%s"', $name, $this->className));
    }

    /**
     * Check if a parameter with the given property name (not necessarily the name) exists in this settings class.
     * @param  string  $name
     * @return bool
     */
    public function hasParameterWithPropertyName(string $name): bool
    {
        return isset($this->parametersByPropertyNames[$name]);
    }

    /**
     * Returns a list of all property names of the parameters in this settings class
     * @return string[]
     */
    public function getPropertyNames(): array
    {
        return array_keys($this->parametersByPropertyNames);
    }

    /**
     * Returns a list of all parameter names (not necessarily the property names) of the parameters in this settings class
     * @return string[]|null
     */
    public function getDefaultGroups(): ?array
    {
        return $this->defaultGroups;
    }

    /**
     * Returns a list of all groups, which are defined on parameters in this settings class
     * @return string[]
     */
    public function getDefinedGroups(): array
    {
        return array_keys($this->parametersByGroups);
    }

    /**
     * Returns a list of all parameters, which belong to the given group.
     * If the group does not exist, an empty array is returned.
     * @param  string  $group
     * @return ParameterMetadata[]
     */
    public function getParametersByGroup(string $group): array
    {
        return $this->parametersByGroups[$group] ?? [];
    }

    /**
     * Returns a list of all parameters, which belong to one of the given groups.
     * The keys of the returned array, are the parameter names (not necessarily the property names).
     * The list is distinct, so no parameter is returned twice.
     * @param  array  $group
     * @return ParameterMetadata[]
     * @phpstan-return array<string, ParameterMetadata>
     */
    public function getParametersWithOneOfGroups(array $group): array
    {
        $tmp = [];
        foreach ($group as $g) {
            $params = $this->getParametersByGroup($g);
            foreach ($params as $param) {
                $tmp[$param->getName()] = $param;
            }
        }

        return $tmp;
    }
}