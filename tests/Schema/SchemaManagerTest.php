<?php

namespace Jbtronics\SettingsBundle\Tests\Schema;

use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;

class SchemaManagerTest extends TestCase
{

    private SchemaManagerInterface $schemaManager;

    public function setUp(): void
    {
        $this->schemaManager = new SchemaManager();
    }

    public function testIsConfigClass(): void
    {
        //Basic classes should not be config classes
        $this->assertFalse($this->schemaManager->isSettingsClass(\DateTime::class));
        $this->assertFalse($this->schemaManager->isSettingsClass(\stdClass::class));

        //But our config class should recognize as such
        $this->assertTrue($this->schemaManager->isSettingsClass(SimpleSettings::class));
    }

    public function testGetSchemaInvalidClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->schemaManager->getSchema(\DateTime::class);
    }

    public function testGetSchema(): void
    {
        $schema = $this->schemaManager->getSchema(SimpleSettings::class);

        $this->assertEquals(SimpleSettings::class, $schema->getClassName());
    }
}
