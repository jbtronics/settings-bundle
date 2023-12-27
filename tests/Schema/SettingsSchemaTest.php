<?php

namespace Jbtronics\SettingsBundle\Tests\Schema;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Metadata\Settings;
use Jbtronics\SettingsBundle\Metadata\SettingsParameter;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
use PHPUnit\Framework\TestCase;

class SettingsSchemaTest extends TestCase
{
    private SettingsSchema $configSchema;
    private Settings $configClass;
    private array $propertyAttributes = [];

    public function setUp(): void
    {
        $this->configClass = new Settings();
        $this->propertyAttributes = [
            'property1' => new SettingsParameter(IntType::class),
            'property2' => new SettingsParameter(StringType::class),
            'property3' => new SettingsParameter(BoolType::class),
        ];

        $this->configSchema = new SettingsSchema(
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
