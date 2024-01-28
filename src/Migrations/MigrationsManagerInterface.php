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


namespace Jbtronics\SettingsBundle\Migrations;

use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;

interface MigrationsManagerInterface
{
    /**
     * Gets an instance of the migrator service for the given settings class or null if no migrator is defined for the settings class.
     * @param  SettingsMetadata  $settingsMetadata
     * @return SettingsMigrationInterface|null
     */
    public function getMigratorInstance(SettingsMetadata $settingsMetadata): ?SettingsMigrationInterface;

    /**
     * Retrieve the version of the currently stored settings.
     * @return int The version number of the stored settings. 0
     */
    public function getCurrentVersion(array $persistedData): int;

    /**
     * Checks if the given settings data needs to be migrated.
     * @param  SettingsMetadata  $settingsMetadata
     * @param  array  $persistedData
     * @param  int|null  $targetVersion The version to which the schema should be migrated. If none is given the target version from the settingsMetadata is used.
     * @return bool
     */
    public function requireUpgrade(SettingsMetadata $settingsMetadata, array $persistedData, ?int $targetVersion = null): bool;

    /**
     * Perform the upgrade for the persisted data.
     * The upgraded new data structure is returned.
     * @param  SettingsMetadata  $settingsMetadata
     * @param  array  $persistedData
     * @param  int|null  $targetVersion The version to which the schema should be migrated. If none is given the target version from the settingsMetadata is used.
     * @return array
     */
    public function performUpgrade(SettingsMetadata $settingsMetadata, array $persistedData, ?int $targetVersion = null): array;
}