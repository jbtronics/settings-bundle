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

use InvalidArgumentException;
use Jbtronics\SettingsBundle\Metadata\EmbeddedSettingsMetadata;
use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration\TestMigration;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

class SettingsMetadataTest extends TestCase
{
    private SettingsMetadata $configSchema;
    private Settings $configClass;
    private array $parameterMetadata = [];

    private array $embeddedMetadata = [];

    //These properties are only used to retrieve real PHP reflection types for the schema hash tests
    private int $intTypeProperty;
    private ?int $nullableIntTypeProperty;
    private string $stringTypeProperty;

    public function setUp(): void
    {
        $this->parameterMetadata = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true, groups: ['group1'], envVar: 'ENV_VAR1'),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, name: 'name2', groups: ['group1', 'group2'], envVar: 'ENV_VAR2', envVarMode: EnvVarMode::OVERWRITE),
            new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: true, name: 'name3',label:  'label3', description: 'description3', groups: ['group2', 'group3']),
        ];

        $this->embeddedMetadata = [
            new EmbeddedSettingsMetadata(self::class, 'embedded1', self::class, groups: ['group1']),
            new EmbeddedSettingsMetadata(self::class, 'embedded2', self::class, groups: ['group2', 'group1']),
        ];

        $this->configSchema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            defaultGroups: ['group1', 'group2'],
            embeddedMetadata: $this->embeddedMetadata,
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
        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
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
        ], $this->configSchema->getParameterPropertyNames());
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
        $this->assertEquals(['group1', 'group2', 'group3'], $this->configSchema->getDefinedParameterGroups());
    }

    public function testGetParametersByGroup(): void
    {
        //Check group 1
        $params = $this->configSchema->getParametersByGroup('group1');
        $this->assertCount(2, $params);
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);

        //Check group 2
        $params = $this->configSchema->getParametersByGroup('group2');
        $this->assertCount(2, $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        //Check group 3
        $params = $this->configSchema->getParametersByGroup('group3');
        $this->assertCount(1, $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        //Check invalid group: Must return an empty array
        $params = $this->configSchema->getParametersByGroup('group4');
        $this->assertIsArray($params);
        $this->assertEmpty($params);
    }

    public function testGetParametersWithOneOfGroups(): void
    {
        $params = $this->configSchema->getParametersWithOneOfGroups(['group1']);
        $this->assertCount(2, $params);
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group2']);
        $this->assertCount(2, $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group3']);
        $this->assertCount(1, $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group2', 'group3']);
        $this->assertCount(2, $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group1', 'group3']);
        $this->assertCount(3, $params);
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);

        $params = $this->configSchema->getParametersWithOneOfGroups(['group1', 'group2', 'group3']);
        $this->assertCount(3, $params);
        $this->assertContains($this->parameterMetadata[0], $params);
        $this->assertContains($this->parameterMetadata[1], $params);
        $this->assertContains($this->parameterMetadata[2], $params);
    }

    public function testMissingMigrator(): void
    {
        $this->expectException(LogicException::class);

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

    public function testGetEmbeddeds(): void
    {
        $this->assertEquals(
            [
                'embedded1' => $this->embeddedMetadata[0],
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettings()
        );
    }

    public function testGetEmbeddedByPropertyName(): void
    {
        $this->assertEquals($this->embeddedMetadata[0], $this->configSchema->getEmbeddedSettingByPropertyName('embedded1'));
        $this->assertEquals($this->embeddedMetadata[1], $this->configSchema->getEmbeddedSettingByPropertyName('embedded2'));
    }

    public function testGetEmbeddedByPropertyNameInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->configSchema->getEmbeddedSettingByPropertyName('embedded3');
    }

    public function testGetEmbeddedsByGroup(): void
    {
        $this->assertEquals(
            [
                'embedded1' => $this->embeddedMetadata[0],
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettingsByGroup('group1')
        );

        $this->assertEquals(
            [
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettingsByGroup('group2')
        );

        //When accessing a group that does not exist, an empty array should be returned
        $this->assertEquals(
            [],
            $this->configSchema->getEmbeddedSettingsByGroup('group3')
        );
    }

    public function testGetEmbeddedsWithOneOfGroups(): void
    {
        $this->assertEquals(
            [
                'embedded1' => $this->embeddedMetadata[0],
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettingsWithOneOfGroups(['group1'])
        );

        $this->assertEquals(
            [
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettingsWithOneOfGroups(['group2'])
        );

        $this->assertEquals(
            [
                'embedded1' => $this->embeddedMetadata[0],
                'embedded2' => $this->embeddedMetadata[1],
            ],
            $this->configSchema->getEmbeddedSettingsWithOneOfGroups(['group1', 'group2'])
        );

        $this->assertEquals(
            [],
            $this->configSchema->getEmbeddedSettingsWithOneOfGroups(['group3'])
        );
    }

    public function testGetParametersWithEnvVar(): void
    {
        //For null all parameters with env vars should be returned
        $params = $this->configSchema->getParametersWithEnvVar();
        $this->assertEquals([
            'property1' => $this->parameterMetadata[0],
            'name2' => $this->parameterMetadata[1],
        ], $params);


        //For a specific env var only the parameter with this env var should be returned
        $params = $this->configSchema->getParametersWithEnvVar(EnvVarMode::INITIAL);
        $this->assertEquals([
            'property1' => $this->parameterMetadata[0],
        ], $params);

        //If we give an array, we should get all parameters with the given env vars
        $params = $this->configSchema->getParametersWithEnvVar([EnvVarMode::INITIAL, EnvVarMode::OVERWRITE]);
        $this->assertEquals([
            'property1' => $this->parameterMetadata[0],
            'name2' => $this->parameterMetadata[1],
        ], $params);

        //If we give an mode, which is not used, we should get an empty array
        $params = $this->configSchema->getParametersWithEnvVar(EnvVarMode::OVERWRITE_PERSIST);
        $this->assertEmpty($params);

        //If we give an array with a mode, which is not used, we should get an empty array
        $params = $this->configSchema->getParametersWithEnvVar([EnvVarMode::OVERWRITE_PERSIST]);
        $this->assertEmpty($params);
    }

    public function testGetCacheAffectingEnvVars(): void
    {
        $this->assertSame(['ENV_VAR2'], $this->configSchema->getCacheAffectingEnvVars());
    }

    public function testGetCacheAffectingEnvVarsStripsEnvProcessors(): void
    {
        $metadata = new SettingsMetadata(
            className: self::class,
            parameterMetadata: [
                new ParameterMetadata(self::class, 'property1', BoolType::class, nullable: true, envVar: 'bool:ENV_VAR1', envVarMode: EnvVarMode::OVERWRITE),
                new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, envVar: 'default:fallback:ENV_VAR2', envVarMode: EnvVarMode::OVERWRITE_PERSIST),
                //Same env var with a different processor must not be listed twice
                new ParameterMetadata(self::class, 'property3', StringType::class, nullable: true, envVar: 'ENV_VAR1', envVarMode: EnvVarMode::OVERWRITE),
            ],
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
        );

        $this->assertSame(['ENV_VAR1', 'ENV_VAR2'], $metadata->getCacheAffectingEnvVars());
    }

    private function phpType(string $property): Type
    {
        return TypeResolver::create()->resolve(new \ReflectionProperty(self::class, $property));
    }

    /**
     * Creates a settings metadata instance with a fixed base schema, which can be modified by the given arguments.
     */
    private function createSchemaHashMetadata(
        ?array $parameters = null,
        ?array $embeddeds = null,
        string $className = self::class,
        ?int $version = null,
        string $name = 'test',
    ): SettingsMetadata {
        $parameters ??= [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false, phpType: $this->phpType('intTypeProperty')),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: false, phpType: $this->phpType('stringTypeProperty')),
        ];

        $embeddeds ??= [
            new EmbeddedSettingsMetadata(self::class, 'embedded1', self::class),
        ];

        return new SettingsMetadata(
            className: $className,
            parameterMetadata: $parameters,
            storageAdapter: InMemoryStorageAdapter::class,
            name: $name,
            version: $version,
            migrationService: $version !== null ? TestMigration::class : null,
            embeddedMetadata: $embeddeds,
        );
    }

    public function testGetSchemaHash(): void
    {
        $hash = $this->createSchemaHashMetadata()->getSchemaHash();
        $this->assertNotEmpty($hash);

        //The hash must be deterministic for identical schemas
        $this->assertSame($hash, $this->createSchemaHashMetadata()->getSchemaHash());

        //The global test object must also have a hash, even without PHP types set
        $this->assertNotEmpty($this->configSchema->getSchemaHash());
        $this->assertNotSame($hash, $this->configSchema->getSchemaHash());
    }

    public function testGetSchemaHashIndependentOfParameterOrder(): void
    {
        $param1 = new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false, phpType: $this->phpType('intTypeProperty'));
        $param2 = new ParameterMetadata(self::class, 'property2', StringType::class, nullable: false, phpType: $this->phpType('stringTypeProperty'));

        $this->assertSame(
            $this->createSchemaHashMetadata(parameters: [$param1, $param2])->getSchemaHash(),
            $this->createSchemaHashMetadata(parameters: [$param2, $param1])->getSchemaHash()
        );
    }

    public function testGetSchemaHashIgnoresNonSchemaChanges(): void
    {
        $hash = $this->createSchemaHashMetadata()->getSchemaHash();

        //Labels, descriptions, groups, env vars and the parameter name do not affect the data shape
        $parameters = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false, name: 'otherName',
                label: 'label', description: 'description', groups: ['group1'], envVar: 'ENV_VAR1', envVarMode: EnvVarMode::OVERWRITE,
                phpType: $this->phpType('intTypeProperty')),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: false, phpType: $this->phpType('stringTypeProperty')),
        ];
        $this->assertSame($hash, $this->createSchemaHashMetadata(parameters: $parameters)->getSchemaHash());

        //The short name of the settings class does not affect the data shape
        $this->assertSame($hash, $this->createSchemaHashMetadata(name: 'other')->getSchemaHash());

        //Groups and labels of embeddeds do not affect the data shape
        $embeddeds = [
            new EmbeddedSettingsMetadata(self::class, 'embedded1', self::class, groups: ['group1'], label: 'label'),
        ];
        $this->assertSame($hash, $this->createSchemaHashMetadata(embeddeds: $embeddeds)->getSchemaHash());
    }

    public function testGetSchemaHashChangesWithClassAndVersion(): void
    {
        $hash = $this->createSchemaHashMetadata()->getSchemaHash();

        $this->assertNotSame($hash, $this->createSchemaHashMetadata(className: ParameterMetadataTest::class)->getSchemaHash());

        $hashV1 = $this->createSchemaHashMetadata(version: 1)->getSchemaHash();
        $hashV2 = $this->createSchemaHashMetadata(version: 2)->getSchemaHash();
        $this->assertNotSame($hash, $hashV1);
        $this->assertNotSame($hashV1, $hashV2);
    }

    public function testGetSchemaHashChangesWithParameters(): void
    {
        $hash = $this->createSchemaHashMetadata()->getSchemaHash();
        $param2 = new ParameterMetadata(self::class, 'property2', StringType::class, nullable: false, phpType: $this->phpType('stringTypeProperty'));

        $variants = [
            'removed parameter' => [$param2],
            'added parameter' => [
                new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false, phpType: $this->phpType('intTypeProperty')),
                $param2,
                new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: false),
            ],
            'renamed property' => [
                new ParameterMetadata(self::class, 'propertyRenamed', IntType::class, nullable: false, phpType: $this->phpType('intTypeProperty')),
                $param2,
            ],
            'changed parameter type' => [
                new ParameterMetadata(self::class, 'property1', BoolType::class, nullable: false, phpType: $this->phpType('intTypeProperty')),
                $param2,
            ],
            'changed nullability' => [
                new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true, phpType: $this->phpType('intTypeProperty')),
                $param2,
            ],
            'changed PHP type' => [
                new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false, phpType: $this->phpType('nullableIntTypeProperty')),
                $param2,
            ],
            'removed PHP type' => [
                new ParameterMetadata(self::class, 'property1', IntType::class, nullable: false),
                $param2,
            ],
        ];

        foreach ($variants as $description => $parameters) {
            $this->assertNotSame($hash, $this->createSchemaHashMetadata(parameters: $parameters)->getSchemaHash(),
                sprintf('The schema hash must change for: %s', $description));
        }
    }

    public function testGetSchemaHashChangesWithEmbeddeds(): void
    {
        $hash = $this->createSchemaHashMetadata()->getSchemaHash();

        $variants = [
            'removed embedded' => [],
            'added embedded' => [
                new EmbeddedSettingsMetadata(self::class, 'embedded1', self::class),
                new EmbeddedSettingsMetadata(self::class, 'embedded2', self::class),
            ],
            'renamed embedded' => [
                new EmbeddedSettingsMetadata(self::class, 'embeddedRenamed', self::class),
            ],
            'changed target class' => [
                new EmbeddedSettingsMetadata(self::class, 'embedded1', ParameterMetadataTest::class),
            ],
        ];

        foreach ($variants as $description => $embeddeds) {
            $this->assertNotSame($hash, $this->createSchemaHashMetadata(embeddeds: $embeddeds)->getSchemaHash(),
                sprintf('The schema hash must change for: %s', $description));
        }
    }
}
