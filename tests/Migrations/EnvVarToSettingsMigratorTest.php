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

use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Migrations\EnvVarToSettingsMigratorInterface;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Storage\StorageAdapterInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EnvVarToSettingsMigratorTest extends KernelTestCase
{
    private EnvVarToSettingsMigratorInterface $service;
    private StorageAdapterInterface $storageAdapter;
    private MetadataManagerInterface $metadataManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(EnvVarToSettingsMigratorInterface::class);
        $this->metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
        $this->storageAdapter = self::getContainer()->get(InMemoryStorageAdapter::class);
    }

    public function testGetAffectedEnvVars(): void
    {
        //Set environment variable to test
        $_ENV['ENV_VALUE2'] = "true";
        $_ENV['ENV_VALUE3'] = "-100.5";

        $affected = $this->service->getAffectedEnvVars(EnvVarSettings::class);
        $this->assertIsArray($affected);
        $this->assertContainsOnly("string", $affected);
        $this->assertEquals(['ENV_VALUE2', 'ENV_VALUE3'], $affected);


        //Unset the environment variable to avoid side effects
        unset($_ENV['ENV_VALUE2']);
        unset($_ENV['ENV_VALUE3']);
    }

    public function testMigrate()
    {
        /** @var SettingsMetadata $metadata */
        $metadata = $this->metadataManager->getSettingsMetadata(EnvVarSettings::class);
        $this->assertEquals(InMemoryStorageAdapter::class, $metadata->getStorageAdapter());


        //Set something to the storage adapter
        $this->storageAdapter->save($metadata->getStorageKey(), [
            'value1' => 'test',
            'value4' => null,
        ]);

        //Set environment variable to test
        $_ENV['ENV_VALUE2'] = "true";
        $_ENV['ENV_VALUE3'] = "-100.5";

        //Migrate the settings
        $this->service->migrate(EnvVarSettings::class);

        //After migration, the storage adapter should contain the migrated values
        $settings = $this->storageAdapter->load($metadata->getStorageKey());
        $this->assertIsArray($settings);

        $this->assertEquals([
            'value1' => 'test',
            'value2' => true,
            'value3' => 123.4, //Mapper overwrites value from env
            'value4' => null,
        ], $settings);

        //Unset the environment variable to avoid side effects
        unset($_ENV['ENV_VALUE2']);
        unset($_ENV['ENV_VALUE3']);
    }
}
