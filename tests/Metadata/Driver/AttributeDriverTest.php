<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Metadata\Driver;

use Jbtronics\SettingsBundle\Metadata\Driver\AttributeDriver;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\InheritedSettings;
use Jbtronics\SettingsBundle\Tests\Fixtures\Settings\YamlConfiguredSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use PHPUnit\Framework\TestCase;

class AttributeDriverTest extends TestCase
{
    private AttributeDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new AttributeDriver([
            __DIR__.'/../../TestApplication/src/Settings/',
        ]);
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

    public function testLoadParameterMetadataChildDeclarationWins(): void
    {
        $parameters = $this->driver->loadParameterMetadata(InheritedSettings::class);

        //The attribute of the child class must be used, not the one of the parent class
        $this->assertSame('child', $parameters['redeclared']->label);

        //A property redeclared without attribute is no parameter anymore
        $this->assertArrayNotHasKey('redeclaredWithoutAttribute', $parameters);

        //Parameters only declared in the parent class (also private ones) are still found
        $this->assertSame('parent', $parameters['inherited']->label);
        $this->assertSame('parent', $parameters['parentPrivate']->label);
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
        $classes = $this->driver->getAllManagedClassNames();
        $this->assertIsArray($classes);
        $this->assertContainsOnly('string', $classes);
        $this->assertContains(SimpleSettings::class, $classes);
    }
}
