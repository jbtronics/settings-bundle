<?php

namespace Jbtronics\SettingsBundle\Tests\Schema;

use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchemaManagerTest extends KernelTestCase
{

    private SchemaManagerInterface $schemaManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->schemaManager = $this->getContainer()->get(SchemaManagerInterface::class);
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
