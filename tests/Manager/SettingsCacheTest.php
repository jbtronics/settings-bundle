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

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsCacheInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CacheableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\NonCloneableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SettingsCacheTest extends KernelTestCase
{
    private SettingsCacheInterface $settingsCache;
    private SettingsManagerInterface  $settingsManager;
    private MetadataManagerInterface $metadataManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->settingsCache = self::getContainer()->get(SettingsCacheInterface::class);
        $this->settingsManager = self::getContainer()->get(SettingsManagerInterface::class);
        $this->metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
    }

    public function testSimple(): void
    {
        $settings = new SimpleSettings();
        $settings->setValue1('changed value1');
        $settings->setValue2(100);
        $metadata = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);

        $this->settingsCache->setData($metadata, $settings);
        $this->assertTrue($this->settingsCache->hasData($metadata));

        $other = new SimpleSettings();
        $ret = $this->settingsCache->applyData($metadata, $other);
        $this->assertSame($other, $ret);

        $this->assertEquals('changed value1', $other->getValue1());
        $this->assertEquals(100, $other->getValue2());

        $this->settingsCache->invalidateData($metadata);
        $this->assertFalse($this->settingsCache->hasData($metadata));
    }

    public function testExceptionOnNotExistingData(): void
    {
        $metadata = $this->metadataManager->getSettingsMetadata(NonCloneableSettings::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No data found in cache for ' . NonCloneableSettings::class);
        $this->settingsCache->applyData($metadata, new NonCloneableSettings());
    }

    public function testEmbedSettings(): void
    {
        $metadata = $this->metadataManager->getSettingsMetadata(EmbedSettings::class);
        /** @var EmbedSettings $settings */
        $settings = $this->settingsManager->get(EmbedSettings::class);

        $settings->bool = false;

        //Must function with complex and circular references
        $this->settingsCache->setData($metadata, $settings);

        $this->assertTrue($this->settingsCache->hasData($metadata));

        $other = (new \ReflectionClass(EmbedSettings::class))->newInstanceWithoutConstructor();
        $this->settingsCache->applyData($metadata, $other);

        $this->assertFalse($other->bool);

        //Embedded settings must not be filled
        $this->assertFalse(isset($other->simpleSettings));
    }

    public function testCacheableSettings(): void
    {
        $metadata = $this->metadataManager->getSettingsMetadata(CacheableSettings::class);
        $settings = new CacheableSettings();

        $settings->bool = false;
        $settings->string = 'changed string';
        $settings->enum = TestEnum::BAZ;
        $settings->dateTime = new \DateTimeImmutable('2024-01-01');
        $settings->array = ['entry1' => 'value1', 'entry2' => 'value2'];

        //Must function with complex and circular references
        $this->settingsCache->setData($metadata, $settings);

        $this->assertTrue($this->settingsCache->hasData($metadata));

        $other = new CacheableSettings();
        $this->settingsCache->applyData($metadata, $other);

        $this->assertFalse($other->bool);
        $this->assertEquals('changed string', $other->string);
        $this->assertEquals(TestEnum::BAZ, $other->enum);
        $this->assertEquals(new \DateTimeImmutable('2024-01-01'), $other->dateTime);
        $this->assertEquals(['entry1' => 'value1', 'entry2' => 'value2'], $other->array);

        //Embedded settings must not be filled
        $this->assertFalse(isset($other->simpleSettings));

        //Invalidate cache to prevent side-effects
        $this->settingsCache->invalidateData($metadata);
    }

    public function testEnvVARInvalidation(): void
    {
        $_ENV['ENV_VALUE2'] = "initial";

        $metadata = $this->metadataManager->getSettingsMetadata(CacheableSettings::class);
        $settings = new CacheableSettings();
        $this->settingsCache->setData($metadata, $settings);
        $this->assertTrue($this->settingsCache->hasData($metadata));

        //Change the env var, the cache must be invalidated
        $_ENV['ENV_VALUE2'] = "changed";
        $this->assertFalse($this->settingsCache->hasData($metadata));
        $this->settingsCache->setData($metadata, $settings);
        $this->assertTrue($this->settingsCache->hasData($metadata));

        unset($_ENV['ENV_VALUE2']);
        //The cache must be invalidated again
        $this->assertFalse($this->settingsCache->hasData($metadata));
    }
}
