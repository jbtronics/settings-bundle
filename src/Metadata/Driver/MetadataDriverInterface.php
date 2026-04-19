<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 * Copyright (c) 2026 Sviatoslav Vysitskyi
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

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Metadata\Driver;

use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

/**
 * A metadata driver is responsible for loading settings metadata from a specific source
 * (e.g. PHP attributes, YAML files, XML files).
 */
interface MetadataDriverInterface
{
    /**
     * Returns whether this driver can provide metadata for the given class, which effectively means that his is a
     * managed settings class under the responsibility of this driver.
     * @param  string  $className  The fully qualified class name
     * @phpstan-var class-string $className
     * @return bool
     */
    public function isSettingsClass(string $className): bool;

    /**
     * Load the class-level settings configuration for the given class.
     * @param  string  $className  The fully qualified class name
     * @phpstan-var class-string $className
     * @return Settings|null The Settings attribute-equivalent object, or null if not managed by this driver
     */
    public function loadClassMetadata(string $className): ?Settings;

    /**
     * Load property-level parameter metadata for the given class.
     * Returns an associative array keyed by property name.
     * @param  string  $className  The fully qualified class name
     * @phpstan-param class-string $className
     * @return array<string, SettingsParameter>
     */
    public function loadParameterMetadata(string $className): array;

    /**
     * Load property-level embedded settings metadata for the given class.
     * Returns an associative array keyed by property name.
     * @param  string  $className  The fully qualified class name
     * @phpstan-param class-string $className
     * @return array<string, EmbeddedSettings>
     */
    public function loadEmbeddedMetadata(string $className): array;

    /**
     * Returns all class names this driver knows about.
     * Used for discovery of settings classes that may not be found by directory scanning.
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getAllManagedClassNames(): array;
}
