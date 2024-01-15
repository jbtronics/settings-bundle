<?php


/*
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

namespace Jbtronics\SettingsBundle\Tests\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\GuessableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MetadataManagerTest extends KernelTestCase
{

    private MetadataManagerInterface $metadataManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->metadataManager = $this->getContainer()->get(MetadataManagerInterface::class);
    }

    public function testIsConfigClass(): void
    {
        //Basic classes should not be config classes
        $this->assertFalse($this->metadataManager->isSettingsClass(\DateTime::class));
        $this->assertFalse($this->metadataManager->isSettingsClass(\stdClass::class));

        //But our config class should recognize as such
        $this->assertTrue($this->metadataManager->isSettingsClass(SimpleSettings::class));

        //This should also work with short names
        $this->assertTrue($this->metadataManager->isSettingsClass('simple'));
    }

    public function testGetSchemaInvalidClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->metadataManager->getSettingsMetadata(\DateTime::class);
    }

    public function testGetSchema(): void
    {
        $schema = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);

        $this->assertEquals(SimpleSettings::class, $schema->getClassName());

        //This should also work with the short name of the class
        $schema2 = $this->metadataManager->getSettingsMetadata('simple');
        $this->assertSame($schema, $schema2);

        //Check that the schema contains the correct parameters
        $paramMetadata = $schema->getParameter('value1');
        $this->assertEquals(StringType::class, $paramMetadata->getType());
        $this->assertEquals('value1', $paramMetadata->getName());
        $this->assertEquals('value1', $paramMetadata->getPropertyName());
        $this->assertFalse($paramMetadata->isNullable());

        $paramMetadata = $schema->getParameter('property2');
        $this->assertEquals(IntType::class, $paramMetadata->getType());
        $this->assertEquals('property2', $paramMetadata->getName());
        $this->assertEquals('value2', $paramMetadata->getPropertyName());
        $this->assertTrue($paramMetadata->isNullable());

        $paramMetadata = $schema->getParameter('value3');
        $this->assertEquals('value3', $paramMetadata->getName());
        $this->assertEquals('value3', $paramMetadata->getPropertyName());
        $this->assertFalse($paramMetadata->isNullable());
    }

    public function testGetSchemaGuessable(): void
    {
        //Test that schema generation works with guessable types
        $schema = $this->metadataManager->getSettingsMetadata(GuessableSettings::class);

        $int_schema = $schema->getParameter('int');
        $this->assertEquals(IntType::class, $int_schema->getType());

        $enum_schema = $schema->getParameter('enum');
        $this->assertEquals(EnumType::class, $enum_schema->getType());
        $this->assertEquals(TestEnum::class, $enum_schema->getOptions()['class']);
    }
}
