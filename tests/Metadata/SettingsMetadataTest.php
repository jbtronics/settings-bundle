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

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration\TestMigration;
use PhpParser\Node\Param;
use PHPUnit\Framework\TestCase;

class SettingsMetadataTest extends TestCase
{
    private SettingsMetadata $configSchema;
    private Settings $configClass;
    private array $parameterMetadata = [];

    public function setUp(): void
    {
        $this->parameterMetadata = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true, groups: ['group1']),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, name: 'name2', groups: ['group1', 'group2']),
            new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: true, name: 'name3',label:  'label3', description: 'description3', groups: ['group2', 'group3']),
        ];

        $this->configSchema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            defaultGroups: ['group1', 'group2'],
        );
    }

    public function testGetClassName(): void
    {
        $this->assertEquals(self::class, $this->configSchema->getClassName());
    }

    public function testGetParameters(): void
    {
        $this->assertEquals([
            'property1' => $this->parameterMetadata[0],
            'name2' => $this->parameterMetadata[1],
            'name3' => $this->parameterMetadata[2],
        ], $this->configSchema->getParameters());
    }

    public function testHasParameter(): void
    {
        $this->assertTrue($this->configSchema->hasParameter('property1'));
        $this->assertTrue($this->configSchema->hasParameter('name2'));
        $this->assertTrue($this->configSchema->hasParameter('name3'));
        $this->assertFalse($this->configSchema->hasParameter('property4'));
    }

    public function testGetParameter(): void
    {
        $this->assertEquals($this->parameterMetadata[0], $this->configSchema->getParameter('property1'));
        $this->assertEquals($this->parameterMetadata[1], $this->configSchema->getParameter('name2'));
        $this->assertEquals($this->parameterMetadata[2], $this->configSchema->getParameter('name3'));
    }

    public function testGetParameterInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->configSchema->getParameter('property4');
    }

    public function testGetParameterByPropertyName(): void
    {
        $this->assertEquals($this->parameterMetadata[0], $this->configSchema->getParameterByPropertyName('property1'));
        $this->assertEquals($this->parameterMetadata[1], $this->configSchema->getParameterByPropertyName('property2'));
        $this->assertEquals($this->parameterMetadata[2], $this->configSchema->getParameterByPropertyName('property3'));
    }

    public function testGetParameterByPropertyNameInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->configSchema->getParameterByPropertyName('property4');
    }

    public function testHasParameterWithPropertyName(): void
    {
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property1'));
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property2'));
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property3'));
        $this->assertFalse($this->configSchema->hasParameterWithPropertyName('property4'));
    }

    public function testGetPropertyNames(): void
    {
        $this->assertEquals([
            'property1',
            'property2',
            'property3',
        ], $this->configSchema->getPropertyNames());
    }

    public function testGetStorageAdapter(): void
    {
        $this->assertEquals(InMemoryStorageAdapter::class, $this->configSchema->getStorageAdapter());
    }

    public function testGetDefaultGroups(): void
    {
        $this->assertEquals(['group1', 'group2'], $this->configSchema->getDefaultGroups());
    }

    public function testGetDefinedGroups(): void
    {
        $this->assertEquals(['group1', 'group2', 'group3'], $this->configSchema->getDefinedGroups());
    }

    public function testGetParametersByGroup(): void
    {
        //Check group 1
        $params = $this->configSchema->getParametersByGroup('group1');
        $this->assertEquals(2, count($params));
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);

        //Check group 2
        $params = $this->configSchema->getParametersByGroup('group2');
        $this->assertEquals(2, count($params));
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        //Check group 3
        $params = $this->configSchema->getParametersByGroup('group3');
        $this->assertEquals(1, count($params));
        $this->assertContains($this->parameterMetadata[2], $params);

        //Check invalid group: Must return an empty array
        $params = $this->configSchema->getParametersByGroup('group4');
        $this->assertIsArray($params);
        $this->assertEmpty($params);
    }

    public function testGetParametersWithOneOfGroups(): void
    {
        $params = $this->configSchema->getParametersWithOneOfGroups(['group1']);
        $this->assertEquals(2, count($params));
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group2']);
        $this->assertEquals(2, count($params));
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group3']);
        $this->assertEquals(1, count($params));
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group2', 'group3']);
        $this->assertEquals(2, count($params));
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group1', 'group3']);
        $this->assertEquals(3, count($params));
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group1', 'group2', 'group3']);
        $this->assertEquals(3, count($params));
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);
    }

    public function testMissingMigrator(): void
    {
        $this->expectException(\LogicException::class);

        $schema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            version: 1,
            migrationService: null
        );
    }

    public function testGetVersion(): void
    {
        //The version is not set on global test object
        $this->assertNull($this->configSchema->getVersion());

        $schema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            version: 1,
            migrationService: TestMigration::class
        );

        $this->assertEquals(1, $schema->getVersion());
    }

    public function testIsVersioned(): void
    {
        //The version is not set on global test object
        $this->assertFalse($this->configSchema->isVersioned());

        $schema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            version: 1,
            migrationService: TestMigration::class
        );

        $this->assertTrue($schema->isVersioned());
    }

    public function testGetMigrationService(): void
    {
        //The version is not set on global test object
        $this->assertNull($this->configSchema->getMigrationService());

        $schema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            version: 1,
            migrationService: TestMigration::class
        );

        $this->assertEquals(TestMigration::class, $schema->getMigrationService());
    }
}
