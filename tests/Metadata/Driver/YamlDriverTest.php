<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Metadata\Driver;

use Jbtronics\SettingsBundle\Metadata\Driver\YamlDriver;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlConfiguredSettings;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlEmbeddedTarget;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlWithEmbeddedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;

class YamlDriverTest extends TestCase
{
    private const YAML_MAPPING_DIR = __DIR__ . '/../../Fixtures/yaml_mappings';

    private YamlDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new YamlDriver([self::YAML_MAPPING_DIR]);
    }

    public function testIsSettingsClassReturnsTrueForYamlConfiguredClass(): void
    {
        $this->assertTrue($this->driver->isSettingsClass(YamlConfiguredSettings::class));
    }

    public function testIsSettingsClassReturnsFalseForAttributeConfiguredClass(): void
    {
        $this->assertFalse($this->driver->isSettingsClass(SimpleSettings::class));
    }

    public function testGetAllManagedClassNames(): void
    {
        $classNames = $this->driver->getAllManagedClassNames();

        $this->assertContains(YamlConfiguredSettings::class, $classNames);
        $this->assertContains(YamlWithEmbeddedSettings::class, $classNames);
        $this->assertNotContains(SimpleSettings::class, $classNames);
    }

    public function testLoadClassMetadata(): void
    {
        $settings = $this->driver->loadClassMetadata(YamlConfiguredSettings::class);

        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertEquals('yaml_configured', $settings->name);
        $this->assertEquals(InMemoryStorageAdapter::class, $settings->storageAdapter);
        $this->assertEquals('YAML Configured Settings', $settings->label);
        $this->assertEquals('Settings configured via YAML', $settings->description);
        $this->assertEquals(['admin'], $settings->groups);
        $this->assertTrue($settings->dependencyInjectable);
        $this->assertFalse($settings->cacheable);
    }

    public function testLoadClassMetadataReturnsNullForUnknownClass(): void
    {
        $this->assertNull($this->driver->loadClassMetadata(SimpleSettings::class));
    }

    public function testLoadParameterMetadata(): void
    {
        $parameters = $this->driver->loadParameterMetadata(YamlConfiguredSettings::class);

        $this->assertCount(3, $parameters);
        $this->assertArrayHasKey('name', $parameters);
        $this->assertArrayHasKey('count', $parameters);
        $this->assertArrayHasKey('enabled', $parameters);

        // Check the 'name' parameter
        $nameParam = $parameters['name'];
        $this->assertInstanceOf(SettingsParameter::class, $nameParam);
        $this->assertEquals(StringType::class, $nameParam->type);
        $this->assertEquals('Name', $nameParam->label);
        $this->assertEquals('The name parameter', $nameParam->description);

        // Check the 'count' parameter (nullable)
        $countParam = $parameters['count'];
        $this->assertEquals(IntType::class, $countParam->type);
        $this->assertTrue($countParam->nullable);

        // Check the 'enabled' parameter (with custom groups)
        $enabledParam = $parameters['enabled'];
        $this->assertEquals(BoolType::class, $enabledParam->type);
        $this->assertEquals(['general'], $enabledParam->groups);
    }

    public function testLoadParameterMetadataReturnsEmptyForUnknownClass(): void
    {
        $this->assertEmpty($this->driver->loadParameterMetadata(SimpleSettings::class));
    }

    public function testLoadEmbeddedMetadata(): void
    {
        $embeddeds = $this->driver->loadEmbeddedMetadata(YamlWithEmbeddedSettings::class);

        $this->assertCount(1, $embeddeds);
        $this->assertArrayHasKey('child', $embeddeds);

        $child = $embeddeds['child'];
        $this->assertInstanceOf(EmbeddedSettings::class, $child);
        $this->assertEquals(YamlEmbeddedTarget::class, $child->target);
        $this->assertEquals('Child Settings', $child->label);
        $this->assertEquals('An embedded child', $child->description);
    }

    public function testLoadEmbeddedMetadataReturnsEmptyForClassWithoutEmbeddeds(): void
    {
        $this->assertEmpty($this->driver->loadEmbeddedMetadata(YamlConfiguredSettings::class));
    }

    public function testDriverWithNonExistentDirectory(): void
    {
        $driver = new YamlDriver(['/non/existent/path']);
        $this->assertEmpty($driver->getAllManagedClassNames());
    }

    public function testDriverWithEmptyPaths(): void
    {
        $driver = new YamlDriver([]);
        $this->assertEmpty($driver->getAllManagedClassNames());
        $this->assertFalse($driver->isSettingsClass(YamlConfiguredSettings::class));
    }
}
