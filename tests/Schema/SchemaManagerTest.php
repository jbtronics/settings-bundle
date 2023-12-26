<?php

namespace Jbtronics\UserConfigBundle\Tests\Schema;

use Jbtronics\UserConfigBundle\Schema\SchemaManager;
use Jbtronics\UserConfigBundle\Schema\SchemaManagerInterface;
use Jbtronics\UserConfigBundle\Tests\TestApplication\Config\SimpleConfig;
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
        $this->assertFalse($this->schemaManager->isConfigClass(\DateTime::class));
        $this->assertFalse($this->schemaManager->isConfigClass(\stdClass::class));

        //But our config class should recognize as such
        $this->assertTrue($this->schemaManager->isConfigClass(SimpleConfig::class));
    }

    public function testGetSchemaInvalidClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->schemaManager->getSchema(\DateTime::class);
    }

    public function testGetSchema(): void
    {
        $schema = $this->schemaManager->getSchema(SimpleConfig::class);

        $this->assertEquals(SimpleConfig::class, $schema->getClassName());
    }
}
