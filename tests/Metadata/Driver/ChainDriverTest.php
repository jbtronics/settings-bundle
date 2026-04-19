<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Metadata\Driver;

use Jbtronics\SettingsBundle\Metadata\Driver\AttributeDriver;
use Jbtronics\SettingsBundle\Metadata\Driver\ChainDriver;
use Jbtronics\SettingsBundle\Metadata\Driver\YamlDriver;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlConfiguredSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;

class ChainDriverTest extends TestCase
{
    private const YAML_MAPPING_DIR = __DIR__ . '/../../Fixtures/yaml_mappings';

    private ChainDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new ChainDriver([
            new AttributeDriver([
                __DIR__.'/../../TestApplication/src/Settings',
            ], ),
            new YamlDriver([self::YAML_MAPPING_DIR]),
        ]);
    }

    public function testIsSettingsClassForAttributeClass(): void
    {
        $this->assertTrue($this->driver->isSettingsClass(SimpleSettings::class));
    }

    public function testIsSettingsClassForYamlClass(): void
    {
        $this->assertTrue($this->driver->isSettingsClass(YamlConfiguredSettings::class));
    }

    public function testIsSettingsClassReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->driver->isSettingsClass(\stdClass::class));
    }

    public function testLoadClassMetadataFromAttributes(): void
    {
        $settings = $this->driver->loadClassMetadata(SimpleSettings::class);
        $this->assertNotNull($settings);
        $this->assertEquals('Simple Settings', $settings->label);
    }

    public function testLoadClassMetadataFromYaml(): void
    {
        $settings = $this->driver->loadClassMetadata(YamlConfiguredSettings::class);
        $this->assertNotNull($settings);
        $this->assertEquals('yaml_configured', $settings->name);
    }

    public function testLoadClassMetadataReturnsNullForUnknown(): void
    {
        $this->assertNull($this->driver->loadClassMetadata(\stdClass::class));
    }

    public function testLoadParameterMetadataFromAttributes(): void
    {
        $params = $this->driver->loadParameterMetadata(SimpleSettings::class);
        $this->assertNotEmpty($params);
        $this->assertArrayHasKey('value1', $params);
    }

    public function testLoadParameterMetadataFromYaml(): void
    {
        $params = $this->driver->loadParameterMetadata(YamlConfiguredSettings::class);
        $this->assertNotEmpty($params);
        $this->assertArrayHasKey('name', $params);
    }

    public function testGetAllManagedClassNamesMergesFromAllDrivers(): void
    {
        $classNames = $this->driver->getAllManagedClassNames();

        // AttributeDriver returns empty, YamlDriver returns its classes
        $this->assertContains(YamlConfiguredSettings::class, $classNames);
    }

    public function testAttributeDriverTakesPrecedence(): void
    {
        // If a class has both attributes and YAML, the attribute driver should win
        // (because it's first in the chain)
        $settings = $this->driver->loadClassMetadata(SimpleSettings::class);
        $this->assertNotNull($settings);
        // Verify it came from attributes (has the attribute-specific label)
        $this->assertEquals('Simple Settings', $settings->label);
    }
}
