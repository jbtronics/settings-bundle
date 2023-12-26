<?php

namespace Jbtronics\UserConfigBundle\Manager;

/**
 * This interface defines the contracts of the configuration manager, which is responsible for managing the configuration
 * and its values.
 */
interface ConfigurationManagerInterface
{
    /**
     * Returns the value filled instance of the given configuration class.
     * @param  string  $configClass
     * @return object
     * @template T of object
     * @phpstan-param class-string<T> $configClass
     * @phpstan-return T
     */
    public function get(string $configClass): object;

    /**
     * Reloads the configuration class from memory provider and overwrites the values in memory.
     * The new instance is returned.
     * @param  string|null $configClass
     * @template T of object
     * @phpstan-param class-string<T> $configClass
     * @phpstan-return T
     */
    public function reload(?string $configClass): object;

    /**
     * Save the configuration class to the storage provider. If no configuration class is given, all configuration
     * classes are saved.
     * @param  string|null  $configClass
     * @return void
     */
    public function save(?string $configClass = null): void;
}