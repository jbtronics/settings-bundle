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

namespace Jbtronics\SettingsBundle\Tests\Migrations;

use Closure;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Migrations\MigrationsManager;
use Jbtronics\SettingsBundle\Migrations\MigrationsManagerInterface;
use Jbtronics\SettingsBundle\Migrations\SettingsMigration;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration\ClassMigration;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\VersionedSettings;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SettingsMigrationTest extends KernelTestCase
{

    private SettingsMetadata $settingsMetadata;

    protected function setUp(): void
    {
        $this->settingsMetadata = $this->createMock(SettingsMetadata::class);
    }

    public function testMissingStepHandlerMethod(): void
    {
        $this->expectException(LogicException::class);

        $migration = new class extends SettingsMigration {
        };
        $migration->migrate($this->settingsMetadata, [], 1, 2);
    }

    public function testStepHandlersCalled(): void
    {
        $migrator = new class extends SettingsMigration {
            protected function migrateToVersion1(array $data, SettingsMetadata $metadata): array
            {
                $data['version1'] = true;
                $data['metadata'] = $metadata;
                return $data;
            }

            protected function migrateToVersion2(array $data, SettingsMetadata $metadata): array
            {
                $data['version2'] = true;
                $data['metadata'] = $metadata;
                return $data;
            }
        };

        $data = $migrator->migrate($this->settingsMetadata, [], 0, 2);

        //Both step handlers should have been called and metadata should be passed to them
        $this->assertEquals([
            'version1' => true,
            'version2' => true,
            'metadata' => $this->settingsMetadata,
        ], $data);
    }

    public function testStepResolverOverride(): void
    {
        $migrator = new class extends SettingsMigration {
            protected function toVersion1(array $data, SettingsMetadata $metadata): array
            {
                $data['version1'] = true;
                $data['metadata'] = $metadata;
                return $data;
            }

            protected function toVersion2(array $data, SettingsMetadata $metadata): array
            {
                $data['version2'] = true;
                $data['metadata'] = $metadata;
                return $data;
            }

            protected function resolveStepHandler(int $version): Closure
            {
                return match ($version) {
                    1 => $this->toVersion1(...),
                    2 => $this->toVersion2(...),
                    default => parent::resolveStepHandler(...),
                };
            }
        };

        $data = $migrator->migrate($this->settingsMetadata, [], 0, 2);

        //Both step handlers should have been called and metadata should be passed to them
        $this->assertEquals([
            'version1' => true,
            'version2' => true,
            'metadata' => $this->settingsMetadata,
        ], $data);
    }

    public function testPHPValueConverterTrait(): void
    {
        //Retrieve Class migration service
        self::bootKernel();

        /** @var MigrationsManager $migrationManager */
        $migrationManager = self::getContainer()->get(MigrationsManagerInterface::class);
        $migrator = $migrationManager->getMigratorService(ClassMigration::class);

        /** @var MetadataManagerInterface $metadataManager */
        $metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
        $metadata = $metadataManager->getSettingsMetadata(VersionedSettings::class);

        $data = $migrator->migrate($metadata, [], 0, 2);

        //Both step handlers should have been called and metadata should be passed to them
        $this->assertEquals([
            'migrated' => true,
            'enum' => 2
        ], $data);
    }
}
