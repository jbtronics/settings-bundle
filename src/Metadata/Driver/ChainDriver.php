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

use Jbtronics\SettingsBundle\Settings\Settings;

/**
 * A metadata driver that chains multiple drivers together.
 * The first driver that claims a class wins.
 * This allows mixing attribute-based and YAML-based settings configuration.
 */
final class ChainDriver implements MetadataDriverInterface
{
    /**
     * @param  MetadataDriverInterface[]  $drivers  Ordered list of drivers to try
     */
    public function __construct(
        private readonly array $drivers,
    ) {
    }

    public function isSettingsClass(string $className): bool
    {
        foreach ($this->drivers as $driver) {
            if ($driver->isSettingsClass($className)) {
                return true;
            }
        }

        return false;
    }

    public function loadClassMetadata(string $className): ?Settings
    {
        foreach ($this->drivers as $driver) {
            if ($driver->isSettingsClass($className)) {
                return $driver->loadClassMetadata($className);
            }
        }

        return null;
    }

    public function loadParameterMetadata(string $className): array
    {
        foreach ($this->drivers as $driver) {
            if ($driver->isSettingsClass($className)) {
                return $driver->loadParameterMetadata($className);
            }
        }

        return [];
    }

    public function loadEmbeddedMetadata(string $className): array
    {
        foreach ($this->drivers as $driver) {
            if ($driver->isSettingsClass($className)) {
                return $driver->loadEmbeddedMetadata($className);
            }
        }

        return [];
    }

    public function getAllManagedClassNames(): array
    {
        $classNames = [];
        foreach ($this->drivers as $driver) {
            foreach ($driver->getAllManagedClassNames() as $className) {
                $classNames[$className] = true;
            }
        }

        return array_keys($classNames);
    }
}
