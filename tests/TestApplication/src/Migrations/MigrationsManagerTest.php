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

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Migrations;

use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Migrations\MigrationsManager;
use Jbtronics\SettingsBundle\Migrations\MigrationsManagerInterface;
use Jbtronics\SettingsBundle\Migrations\SettingsMigrationInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration\TestMigration;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\VersionedSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MigrationsManagerTest extends KernelTestCase
{

    private MigrationsManager $service;

    private MetadataManagerInterface $metadataManager;

    private SettingsMetadata $metadata;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(MigrationsManagerInterface::class);
        $this->metadataManager = self::getContainer()->get(MetadataManagerInterface::class);

        //Retrieve metadata for the VersionedSettings class
        $this->metadata = $this->metadataManager->getSettingsMetadata(VersionedSettings::class);
    }

    public function testGetMigratorService(): void
    {
        //Retrieve a service directly by its id
        $migrator = $this->service->getMigratorService(TestMigration::class);
        $this->assertInstanceOf(TestMigration::class, $migrator);
        $this->assertInstanceOf(SettingsMigrationInterface::class, $migrator);
    }

    public function testGetMigratorInstance(): void
    {
        //Retrieve the migrator instance for the VersionedSettings class
        $migrator = $this->service->getMigratorInstance($this->metadata);
        $this->assertInstanceOf(TestMigration::class, $migrator);
        $this->assertInstanceOf(SettingsMigrationInterface::class, $migrator);
    }

    public function testGetCurrentVersion(): void
    {
        //For an array without meta tag, the current version should be 0
        $this->assertEquals(0, $this->service->getCurrentVersion(['foo' => 'bar']));

        //For an array with meta tag, the current version should be the one stored in the meta tag
        $this->assertEquals(4, $this->service->getCurrentVersion([
            '$META$' => [
                'version' => 4
            ]
        ]));
    }

    public function testRequireUpgrade(): void
    {
        //If the version in the persisted data is not set (migrations not enabled), then a migration is required.
        $this->assertTrue($this->service->requireUpgrade($this->metadata, []));

        //If the stored version is smaller than the desired version, then a migration is required.
        $this->assertTrue($this->service->requireUpgrade($this->metadata, [
            '$META$' => [
                'version' => 3
            ]
        ]));

        //If the stored version is equal to the desired version, then no migration is required.
        $this->assertFalse($this->service->requireUpgrade($this->metadata, [
            '$META$' => [
                'version' => 4
            ]
        ], 4));

        //If the stored version is greater than the desired version, then no migration is required.
        $this->assertFalse($this->service->requireUpgrade($this->metadata, [
            '$META$' => [
                'version' => 8
            ]
        ]));

        //For an settings class without versioning, the migration should never be required.
        $this->assertFalse($this->service->requireUpgrade($this->metadataManager->getSettingsMetadata(SimpleSettings::class), []));
    }

    public function testPerformUpgrade(): void
    {
        $data = [
            '$META$' => [
                'version' => 2
            ],
            'foo' => 'bar'
        ];

        //Perform just a partial upgrade
        $new = $this->service->performUpgrade($this->metadata, $data, 3);

        //The migrator must have been called and contain the new version
        $this->assertEquals([
            '$META$' => [
                'version' => 3
            ],
            'foo' => 'bar',
            'migrated' => true,
            'old' => 2,
            'new' => 3
        ], $new);

        //And then the full upgrade
        $new = $this->service->performUpgrade($this->metadata, $new);

        //The migrator must have been called and contain the new version
        $this->assertEquals([
            '$META$' => [
                'version' => 5
            ],
            'foo' => 'bar',
            'migrated' => true,
            'old' => 3,
            'new' => 5
        ], $new);
    }

    public function testPerformUpgradeNoVersionYet(): void
    {
        $data = [
            'foo' => 'bar'
        ];

        $new = $this->service->performUpgrade($this->metadata, $data);

        //The migrator must have been called and contain the new version
         $this->assertEquals([
             '$META$' => [
                'version' => 5
            ],
            'foo' => 'bar',
            'migrated' => true,
            'old' => 0,
            'new' => 5
        ], $new);
    }

    public function testPerformUpgradeNotRequired(): void
    {
        //If we try to perform migrations on something where it is not requred, then the data should not be changed (and the migrator not called).
        $data = [
            'foo' => 'bar'
        ];

        $simpleMetadata = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);

        $new = $this->service->performUpgrade($simpleMetadata, $data);
        $this->assertSame($data, $new);

        //The same for a versioned settings class where the version is already up to date
        $data = [
            '$META$' => [
                'version' => 5
            ],
            'foo' => 'bar'
        ];

        $new = $this->service->performUpgrade($this->metadata, $data);
        $this->assertSame($data, $new);
    }
}
