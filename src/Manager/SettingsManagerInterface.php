<?php

namespace Jbtronics\SettingsBundle\Manager;

/**
 * This interface defines the contracts of the configuration manager, which is responsible for managing the configuration
 * and its values.
 */
interface SettingsManagerInterface
{
    /**
     * Returns the value filled instance of the given settings class.
     * @param  string  $settingsClass
     * @return object
     * @template T of object
     * @phpstan-param class-string<T> $settingsClass
     * @phpstan-return T
     */
    public function get(string $settingsClass): object;

    /**
     * Reloads the settings class from memory provider and overwrites the values in memory.
     * The new instance is returned.
     * @param  string|object $settings The settings class or the name of the settings class
     * @template T of object
     * @phpstan-param class-string<T> $settingsClass
     * @phpstan-return T
     */
    public function reload(object|string $settings): object;

    /**
     * Save the configuration class to the storage provider. If no configuration class is given, all configuration
     * classes are saved.
     * @param  string|object|null $settings The settings class or the name of the settings class
     * @return void
     */
    public function save(object|string|null $settings = null): void;
}