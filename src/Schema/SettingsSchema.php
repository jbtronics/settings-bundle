<?php

namespace Jbtronics\SettingsBundle\Schema;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;

/**
 * This class represents the schema (structure) of a settings class
 */
class SettingsSchema
{
    private readonly array $parametersByPropertyNames;
    private readonly array $parametersByName;

    public function __construct(
        private readonly string $className,
        array $parameterSchemas,
        private readonly string $storageAdapter,
        private readonly string|null $name = null,
    )
    {
        //Sort the parameters by their property names and names
        $byName = [];
        $byPropertyName = [];

        foreach ($parameterSchemas as $parameterSchema) {
            $byName[$parameterSchema->getName()] = $parameterSchema;
            $byPropertyName[$parameterSchema->getPropertyName()] = $parameterSchema;
        }

        $this->parametersByName = $byName;
        $this->parametersByPropertyNames = $byPropertyName;
    }

    /**
     * Returns the class name of the configuration class, which is managed by this schema.
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    public function getName(): string
    {
        return $this->name ?? $this->className;
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
     * Retrieve all parameter schemas of this settings class in the form of an associative array, where the key is the
     * parameter name (not necessarily the property name) and the value is the parameter schema.
     * @return array<string, ParameterSchema>
     */
    public function getParameters(): array
    {
        return $this->parametersByName;
    }

    /**
     * Retrieve the parameter schema of the parameter with the given name (not necessarily the property name)
     * @param  string  $name
     * @return ParameterSchema
     */
    public function getParameter(string $name): ParameterSchema
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
     * Retrieve the parameter schema of the parameter with the given property name (not necessarily the name).
     * @param  string  $name
     * @return ParameterSchema
     */
    public function getParameterByPropertyName(string $name): ParameterSchema
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
}