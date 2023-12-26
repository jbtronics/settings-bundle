<?php

namespace Jbtronics\UserConfigBundle\Tests\TestApplication\Schema;

use Jbtronics\UserConfigBundle\Metadata\ConfigClass;
use Jbtronics\UserConfigBundle\Metadata\ConfigEntry;
use Jbtronics\UserConfigBundle\Schema\ConfigSchema;
use Jbtronics\UserConfigBundle\Tests\TestApplication\Config\SimpleConfig;
use PHPUnit\Framework\TestCase;

class ConfigSchemaTest extends TestCase
{
    private ConfigSchema $configSchema;
    private ConfigClass $configClass;
    private array $propertyAttributes = [];

    public function setUp(): void
    {
        $this->configClass = new ConfigClass();
        $this->propertyAttributes = [
            'property1' => new ConfigEntry('testType'),
            'property2' => new ConfigEntry('testType'),
            'property3' => new ConfigEntry('testType'),
        ];

        $this->configSchema = new ConfigSchema(
            'myClassName',
            $this->configClass,
            $this->propertyAttributes
        );
    }

    public function testGetClassName(): void
    {
        $this->assertEquals('myClassName', $this->configSchema->getClassName());
    }

    public function testGetConfigClassAttribute(): void
    {
        $this->assertEquals($this->configClass, $this->configSchema->getConfigClassAttribute());
    }

    public function testGetConfigEntryPropertyNames(): void
    {
        $this->assertEquals(['property1', 'property2', 'property3'], $this->configSchema->getConfigEntryPropertyNames());
    }

    public function testGetConfigEntryAttributes(): void
    {
        $this->assertEquals($this->propertyAttributes, $this->configSchema->getConfigEntryAttributes());
    }

    public function testGetConfigEntryAttribute()
    {
        $this->assertEquals($this->propertyAttributes['property1'], $this->configSchema->getConfigEntryAttribute('property1'));
    }
}
