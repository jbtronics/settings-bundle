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

use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Symfony\Component\DependencyInjection\ServiceLocator;

class MigrationsManager implements MigrationsManagerInterface
{

    /** @var string The key of the current version stored in the meta array */
    public const META_VERSION_KEY = 'version';

    public function __construct(
        private readonly ServiceLocator $locator
    )
    {
    }

    /**
     * Retrieves the given migrator service from the service locator.
     * @param  string  $serviceId
     * @return SettingsMigrationInterface
     */
    public function getMigratorService(string $serviceId): SettingsMigrationInterface
    {
        return $this->locator->get($serviceId);
    }

    public function getMigratorInstance(SettingsMetadata $settingsMetadata): ?SettingsMigrationInterface
    {
        $migratorServiceId = $settingsMetadata->getMigrationService();
        if ($migratorServiceId === null) {
            return null;
        }

        return $this->getMigratorService($migratorServiceId);
    }

    public function getCurrentVersion(array $persistedData): int
    {
        //Try to extract the version from the persisted data
        //If no version was persisted, we assume 0
        return $persistedData[SettingsHydrator::META_KEY][self::META_VERSION_KEY] ?? 0;
    }

    public function requireUpgrade(SettingsMetadata $settingsMetadata, array $persistedData, ?int $targetVersion = null): bool
    {
        //Resolve target version if not explicitly given
        if ($targetVersion === null) {
            $targetVersion = $settingsMetadata->getVersion();
        }

        //If the desired version in the settings metadata is not set (migrations not enabled), then no migration is required.
        if ($targetVersion === null) {
            return false;
        }

        //Otherwise we check if the desired version is greater than the current one
        return $targetVersion > $this->getCurrentVersion($persistedData);
    }

    public function performUpgrade(
        SettingsMetadata $settingsMetadata,
        array $persistedData,
        ?int $targetVersion = null
    ): array {
        //Resolve target version if not explicitly given
        if ($targetVersion === null) {
            $targetVersion = $settingsMetadata->getVersion();
        }

        //If we don't require an update, just return the array untouched
        if (!$this->requireUpgrade($settingsMetadata, $persistedData, $targetVersion)) {
            return $persistedData;
        }

        //Otherwise retrieve the migrator service
        $migrator = $this->getMigratorInstance($settingsMetadata);

        if ($migrator === null) {
            throw new \LogicException(sprintf('The settings class "%s" requires a migration, but no migrator service is defined.', $settingsMetadata->getClassName()));
        }

        //And perform migration
        $newData = $migrator->migrate($settingsMetadata, $persistedData, $this->getCurrentVersion($persistedData), $targetVersion);

        //Add the new version info to the settings data
        $newData[SettingsHydrator::META_KEY][self::META_VERSION_KEY] = $targetVersion;

        return $newData;
    }
}