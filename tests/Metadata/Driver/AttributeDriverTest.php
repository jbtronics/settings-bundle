<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Metadata\Driver;

use Jbtronics\SettingsBundle\Metadata\Driver\AttributeDriver;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlConfiguredSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use PHPUnit\Framework\TestCase;

class AttributeDriverTest extends TestCase
{
    private AttributeDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new AttributeDriver();
    }

    public function testIsSettingsClassReturnsTrueForAnnotatedClass(): void
    {
        $this->assertTrue($this->driver->isSettingsClass(SimpleSettings::class));
    }

    public function testIsSettingsClassReturnsFalseForPlainClass(): void
    {
        $this->assertFalse($this->driver->isSettingsClass(YamlConfiguredSettings::class));
    }

    public function testIsSettingsClassReturnsFalseForNonExistentClass(): void
    {
        $this->assertFalse($this->driver->isSettingsClass('NonExistent\\Class'));
    }

    public function testLoadClassMetadataReturnsSettingsForAnnotatedClass(): void
    {
        $settings = $this->driver->loadClassMetadata(SimpleSettings::class);
        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertNotNull($settings->storageAdapter);
        $this->assertEquals('Simple Settings', $settings->label);
    }

    public function testLoadClassMetadataReturnsNullForPlainClass(): void
    {
        $this->assertNull($this->driver->loadClassMetadata(YamlConfiguredSettings::class));
    }

    public function testLoadParameterMetadataReturnsParameters(): void
    {
        $parameters = $this->driver->loadParameterMetadata(SimpleSettings::class);

        $this->assertNotEmpty($parameters);
        // SimpleSettings has value1, value2, value3
        $this->assertArrayHasKey('value1', $parameters);
        $this->assertArrayHasKey('value2', $parameters);
        $this->assertArrayHasKey('value3', $parameters);
        $this->assertContainsOnlyInstancesOf(SettingsParameter::class, $parameters);
    }

    public function testLoadParameterMetadataReturnsEmptyForPlainClass(): void
    {
        $parameters = $this->driver->loadParameterMetadata(YamlConfiguredSettings::class);
        $this->assertEmpty($parameters);
    }

    public function testLoadEmbeddedMetadataReturnsEmbeddeds(): void
    {
        $embeddeds = $this->driver->loadEmbeddedMetadata(EmbedSettings::class);
        $this->assertNotEmpty($embeddeds);
    }

    public function testGetAllManagedClassNamesReturnsEmpty(): void
    {
        // AttributeDriver relies on directory scanning, not self-discovery
        $this->assertEmpty($this->driver->getAllManagedClassNames());
    }
}
