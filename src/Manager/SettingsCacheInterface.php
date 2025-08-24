<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;

/**
 * This service is responsible for storing and retrieving cached settings data.
 * It works by saving an array containing all settings data in the cache, and apply it to the settings object when needed.
 * @internal
 */
interface SettingsCacheInterface
{
    /**
     * Checks if the cache contains data for the given settings object.
     * @param  SettingsMetadata  $metadata
     * @return bool True if the cache contains data, false otherwise
     */
    public function hasData(SettingsMetadata $metadata): bool;

    /**
     * Applies the cached data to the given settings object.
     * Only the parameter properties of the settings object are updated. Embedded settings and other properties are not affected.
     * It throws an exception if no data was saved in the cache for the given settings object.
     * Therefore, use hasData() to check if data is available.
     * @throws \RuntimeException If no data was saved in the cache for the given settings object
     * @phpstan-template T of object
     * @param  SettingsMetadata  $metadata
     * @param  object  $data
     * @phpstan-param T $data
     * @return object The data object with the cached data applied (same instance)
     * @phpstan-return T
     */
    public function applyData(SettingsMetadata $metadata, object $data): object;

    /**
     * Saves the data of the given settings object to the cache.
     * @param  SettingsMetadata  $settings
     * @param  object  $value
     * @return void
     */
    public function setData(SettingsMetadata $settings, object $value): void;

    /**
     * Invalidates/remove the cached data for the given settings object.
     * Afterward, hasData() will return false for the given settings object.
     * @param  SettingsMetadata  $settings
     * @return void
     */
    public function invalidateData(SettingsMetadata $settings): void;

    /**
     * Invalidates/removes all cached data for all settings objects.
     * Afterward, hasData() will return false for all settings objects.
     * @return void
     */
    public function invalidateAll(): void;
}