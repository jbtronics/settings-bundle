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

use DateTime;
use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CircularEmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\GuessableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration\TestMigration;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\ValidatableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\VersionedSettings;
use LogicException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MetadataManagerTest extends KernelTestCase
{

    private MetadataManagerInterface $metadataManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
    }

    public function testIsConfigClass(): void
    {
        //Basic classes should not be config classes
        $this->assertFalse($this->metadataManager->isSettingsClass(DateTime::class));
        $this->assertFalse($this->metadataManager->isSettingsClass(stdClass::class));

        //But our config class should recognize as such
        $this->assertTrue($this->metadataManager->isSettingsClass(SimpleSettings::class));

        //This should also work with short names
        $this->assertTrue($this->metadataManager->isSettingsClass('simple'));
    }

    public function testGetSchemaInvalidClass(): void
    {
        $this->expectException(LogicException::class);
        $this->metadataManager->getSettingsMetadata(DateTime::class);
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

    public function testGroups(): void
    {
        $schema = $this->metadataManager->getSettingsMetadata(ValidatableSettings::class);

        //The default groups should be set correctly
        $this->assertEquals(['default'], $schema->getDefaultGroups());

        //value1 must inherit the default groups
        $paramMetadata = $schema->getParameterByPropertyName('value1');
        $this->assertEquals(['default'], $paramMetadata->getGroups());
        //And it should show up in the default group
        $this->assertContains($paramMetadata, $schema->getParametersByGroup('default'));

        //value2 must have the defined groups
        $paramMetadata = $schema->getParameterByPropertyName('value2');
        $this->assertEquals(['group1', 'group2'], $paramMetadata->getGroups());
        $this->assertContains($paramMetadata, $schema->getParametersByGroup('group1'));
        $this->assertContains($paramMetadata, $schema->getParametersByGroup('group2'));
    }

    public function testVersioning(): void
    {
        //For a non versioned class, the version should be null
        $schema = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);
        $this->assertNull($schema->getVersion());
        $this->assertFalse($schema->isVersioned());
        $this->assertNull($schema->getMigrationService());

        //For a versioned class, the version should be set
        $schema = $this->metadataManager->getSettingsMetadata(VersionedSettings::class);
        $this->assertEquals(VersionedSettings::VERSION, $schema->getVersion());
        $this->assertTrue($schema->isVersioned());
        $this->assertEquals(TestMigration::class, $schema->getMigrationService());
    }

    public function testEmbeddeds(): void
    {
        //Test that embedded settings are correctly recognized
        $schema = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);
        $this->assertEmpty($schema->getEmbeddedSettings());

        //Embedded settings should be recognized
        $schema = $this->metadataManager->getSettingsMetadata(EmbedSettings::class);
        $embeddeds = $schema->getEmbeddedSettings();
        $this->assertCount(2, $embeddeds);
        $this->assertEquals('simpleSettings', $embeddeds['simpleSettings']->getPropertyName());
        $this->assertEquals('circularSettings', $embeddeds['circularSettings']->getPropertyName());

        //The target class should be set correctly
        $this->assertEquals(SimpleSettings::class, $embeddeds['simpleSettings']->getTargetClass());
        $this->assertEquals(CircularEmbedSettings::class, $embeddeds['circularSettings']->getTargetClass());

        //Test that the groups are correctly inherited
        $this->assertEquals(['default'], $embeddeds['simpleSettings']->getGroups());
        $this->assertEquals(['group1'], $embeddeds['circularSettings']->getGroups());
    }

    public function testResolveEmbeddedCascade(): void
    {
        //For a settings class, without embedded settings, the result should be an empty array
        $this->assertEquals([SimpleSettings::class], $this->metadataManager->resolveEmbeddedCascade(SimpleSettings::class));

        //For a settings class, with embedded settings, the result should be an array with the embedded settings
        $cascade = $this->metadataManager->resolveEmbeddedCascade(EmbedSettings::class);
        $this->assertEqualsCanonicalizing([
            EmbedSettings::class,
            SimpleSettings::class,
            CircularEmbedSettings::class,
            GuessableSettings::class,
        ], $cascade);
    }

    public function testEnvVarOptions(): void
    {
        $schema = $this->metadataManager->getSettingsMetadata(EnvVarSettings::class);

        $value3 = $schema->getParameter('value3');
        $this->assertEquals('value3', $value3->getName());
        $this->assertSame('ENV_VALUE3', $value3->getEnvVar());
        $this->assertSame([EnvVarSettings::class, 'envVarMapper'], $value3->getEnvVarMapper());
        $this->assertSame(EnvVarMode::INITIAL, $value3->getEnvVarMode());
    }
}
