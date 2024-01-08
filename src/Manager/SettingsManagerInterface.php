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

/**
 * This interface defines the contracts of the configuration manager, which is responsible for managing the configuration
 * and its values.
 */
interface SettingsManagerInterface
{
    /**
     * Returns the value filled instance of the given settings class.
     * @param  string  $settingsClass The settings class to get the instance for. This can either be a class string, or the short name of the settings class.
     * @return object
     * @template T of object
     * @phpstan-param string|class-string<T> $settingsClass
     * @phpstan-return ($settingsClass is class-string<T> ? T : object)
     */
    public function get(string $settingsClass): object;

    /**
     * Reloads the settings class from memory provider and overwrites the values in memory.
     * The new instance is returned.
     * @param  string|object $settings The settings class or the name of the settings class
     * @template T of object
     * @phpstan-param class-string<T>|object $settings
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

    /**
     * Resets the given settings class to their default values.
     * @param  object|string $settings The settings class or the name of the settings class
     * @return void
     */
    public function resetToDefaultValues(object|string $settings): void;
}