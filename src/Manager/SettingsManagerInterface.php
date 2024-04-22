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
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

/**
 * This interface defines the contracts of the configuration manager, which is responsible for managing the configuration
 * and its values.
 */
interface SettingsManagerInterface
{
    /**
     * Returns the value filled instance of the given settings class.
     * @param  string  $settingsClass The settings class to get the instance for. This can either be a class string, or the short name of the settings class.
     * @param  bool $lazy If true, the instance is lazy loaded if not already in memory yet, meaning that the instance is only retrieved from the storage provider when it is accessed for the first time.
     * If false, the instance is always initialized by this method.
     * @return object
     * @template T of object
     * @phpstan-param string|class-string<T> $settingsClass
     * @phpstan-return ($settingsClass is class-string<T> ? T : object)
     */
    public function get(string $settingsClass, bool $lazy = false): object;

    /**
     * Reloads the settings class from memory provider and overwrites the values in memory.
     * The new instance is returned.
     * @param  object|string  $settings  The settings class or the name of the settings class
     * @phpstan-param class-string<T>|T $settings
     * @param  bool  $cascade  If true, all embedded settings (and their embeds) are also reloaded
     * @return object
     * @template T of object
     * @phpstan-return T
     */
    public function reload(object|string $settings, bool $cascade = true): object;

    /**
     * Save the configuration class to the storage provider. If no configuration class is given, all configuration
     * classes are saved.
     * If the settings class is not valid, a SettingsNotValidException is thrown.
     * @param  object|string|array|null  $settings  The settings class or the name of the settings class
     * @param  bool  $cascade  If true, all embedded settings (and their embeds) of the given settings class are also saved
     * @return void
     * @throws SettingsNotValidException
     */
    public function save(object|string|null|array $settings = null, bool $cascade = true): void;

    /**
     * Resets the given settings class to their default values.
     * @param  object|string $settings The settings class or the name of the settings class
     * @return void
     */
    public function resetToDefaultValues(object|string $settings): void;

    /**
     * Checks if the given property of the given settings class was overwritten by an environment variable.
     * This only affects parameters with the EnvVarMode::OVERRIDE or OVERRIDE_PERSIST mode.
     * @template T of object
     * @param  object|string  $settings The settings class or name of a settings class
     * @phpstan-param class-string<T>|T $settings
     * @param  string|ParameterMetadata|\ReflectionProperty  $property The property name or the parameter metadata, which should be checked
     * @return bool True if the property was overwritten by an environment variable, false otherwise
     */
    public function isEnvVarOverwritten(object|string $settings, string|ParameterMetadata|\ReflectionProperty $property): bool;

    /**
     * Creates a temporary copy of the given settings class as a new instance, which can be modified without affecting
     * the original settings class. Call mergeTemporaryCopy() to apply the changes to the original settings instance,
     * shared across the application.
     * @template T of object
     * @param  object|string  $settings
     * @phpstan-param class-string<T>|T $settings
     * @return object
     * @phpstan-return T
     */
    public function createTemporaryCopy(object|string $settings): object;

    /**
     * Merges the temporary copy of the given settings class back to the original settings instance.
     * If the settings class is not valid, a SettingsNotValidException is thrown.
     * @template T of object
     * @param  object|string  $copy
     * @phpstan-param T $copy
     * @param  bool  $recursive If true, the merge operation is also executed on all embedded settings objects, otherwise only the top level settings object is merged
     * @return void
     * @template T of object
     * @throws SettingsNotValidException
     */
    public function mergeTemporaryCopy(object|string $copy, bool $recursive = true): void;
}