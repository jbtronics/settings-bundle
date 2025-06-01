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

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsCacheInterface;
use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CacheableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\VersionedSettings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsHydratorTest extends WebTestCase
{
    private SettingsHydrator $service;
    private MetadataManagerInterface $schemaManager;
    private InMemoryStorageAdapter $storageAdapter;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsHydratorInterface::class);
        $this->schemaManager = self::getContainer()->get(MetadataManagerInterface::class);
        $this->storageAdapter = self::getContainer()->get(InMemoryStorageAdapter::class);
    }

    public function testToNormalizedRepresentation(): void
    {
        $test = new SimpleSettings();
        $test->setValue1('test');
        $test->setValue2(123);
        $test->setValue3(true);

        $schema = $this->schemaManager->getSettingsMetadata(SimpleSettings::class);
        $normalized = $this->service->toNormalizedRepresentation($test, $schema);

        $this->assertIsArray($normalized);
        $this->assertEquals([
            'value1' => 'test',
            //The name of the property is changed to 'property2' in the schema
            'property2' => 123,
            'value3' => true,
        ], $normalized);
    }

    public function testApplyNormalizedRepresentation(): void
    {
        $test = new SimpleSettings();
        //Assert the default values
        $this->assertEquals('default', $test->getValue1());
        $this->assertNull($test->getValue2());
        $this->assertFalse($test->getValue3());

        $data = [
                'value1' => 'test',
                //The name of the property is changed to 'property2' in the schema
                'property2' => 123,
                //Value 3 is left out, so it should not be changed
                //'value3' => true,
        ];

        $schema = $this->schemaManager->getSettingsMetadata(SimpleSettings::class);

        $this->service->applyNormalizedRepresentation($data, $test, $schema);

        //Assert the changed values
        $this->assertEquals('test', $test->getValue1());
        $this->assertEquals(123, $test->getValue2());
        //Value 3 must be left unchanged
        $this->assertFalse($test->getValue3());
    }

    public function testPersistAndHydrate(): void
    {
        $test = new SimpleSettings();
        //Change the values of the settings object
        $test->setValue1('test');
        $test->setValue2(123);
        $test->setValue3(true);

        $schema = $this->schemaManager->getSettingsMetadata(SimpleSettings::class);
        //Ensure that we use the InMemoryStorageAdapter
        $this->assertEquals(InMemoryStorageAdapter::class, $schema->getStorageAdapter());

        //Persist the settings object
        $this->service->persist($test, $schema);

        //Create a new settings object
        $test2 = new SimpleSettings();

        //Hydrate the new settings object
        $this->service->hydrate($test2, $schema);

        //Assert that the values are the same as in the original settings object
        $this->assertEquals($test->getValue1(), $test2->getValue1());
        $this->assertEquals($test->getValue2(), $test2->getValue2());
        $this->assertEquals($test->getValue3(), $test2->getValue3());
    }

    public function testPersistVersioned(): void
    {
        $test = new VersionedSettings();
        $schema = $this->schemaManager->getSettingsMetadata(VersionedSettings::class);

        $this->service->persist($test, $schema);

        //The storage adapter should contain the version info
        $data = $this->storageAdapter->load($schema->getStorageKey());
        $this->assertArrayHasKey(SettingsHydrator::META_KEY, $data);
        $this->assertArrayHasKey('version', $data[SettingsHydrator::META_KEY]);
        $this->assertEquals(VersionedSettings::VERSION, $data[SettingsHydrator::META_KEY]['version']);
    }

    public function testHydrateVersioned(): void
    {
        $test = new VersionedSettings();
        $this->assertFalse($test->migrated);
        $this->assertNull($test->old);
        $this->assertNull($test->new);

        $schema = $this->schemaManager->getSettingsMetadata(VersionedSettings::class);

        //Prepare the storage adapter with some dummy data to migrate
        $this->storageAdapter->save($schema->getStorageKey(), [
            '$META$' => [
                'version' => 1,
            ],
            'foo' => 'bar',
        ]);

        //Hydrate the settings object. This should work without any errors.
        $test = $this->service->hydrate($test, $schema);

        //Afterwards the settings object should be hydrated with the migrated data
        $this->assertTrue($test->migrated);
        $this->assertEquals(1, $test->old);
        $this->assertEquals(VersionedSettings::VERSION, $test->new);

        //The storage adapter should contain the version info and migrated data, as it was saved after the migration
        $data = $this->storageAdapter->load($schema->getStorageKey());
        $this->assertEquals([
            '$META$' => [
                'version' => VersionedSettings::VERSION,
            ],
            'foo' => 'bar',
            'migrated' => true,
            'old' => 1,
            'new' => VersionedSettings::VERSION,
        ], $data);
    }

    public function testHydrateEnvVars(): void
    {
        //Set Env vars
        $_ENV['ENV_VALUE1'] = "should not be applied";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";


        $test = new EnvVarSettings();
        $schema = $this->schemaManager->getSettingsMetadata(EnvVarSettings::class);

        //Prepare the storage adapter with some dummy data to migrate
        $this->storageAdapter->save($schema->getStorageKey(), [
            'value1' => "stored",
            'value3' => -123,
        ]);

        //Hydrate the settings object. This should work without any errors.
        $test = $this->service->hydrate($test, $schema);

        //Afterwards the settings object should be hydrated
        $this->assertEquals('stored', $test->value1);
        //But the value should be overwritten by the env var
        $this->assertEquals(123.4, $test->value3);

        //Values which are not in the storage adapter should still have their original values
        $this->assertTrue($test->value2);

        $this->assertTrue($test->value4);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

    public function testPersistEnvVars(): void
    {
        //Set Env vars
        $_ENV['ENV_VALUE1'] = "should not be applied";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";

        $schema = $this->schemaManager->getSettingsMetadata(EnvVarSettings::class);

        //Prepare the storage adapter with some dummy data to migrate
        $this->storageAdapter->save($schema->getStorageKey(), [
            'value1' => "stored",
            'value3' => -123,
            'value4' => null,
        ]);

        //Hydrate the settings object. This should work without any errors.
        $test = $this->service->hydrate(new EnvVarSettings(), $schema);

        //Modify the settings object
        $test->value1 = 'changed';

        //Persist the settings object
        $this->service->persist($test, $schema);

        //The storage adapter should contain only the modified data and the overwrite persist value
        $data = $this->storageAdapter->load($schema->getStorageKey());
        $this->assertEquals([
            'value1' => 'changed',
            'value3' => -123,
            'value4' => true,
            'value5' => 0.0
        ], $data);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

    public function testApplyEnvVariableOverwrites(): void
    {
        //Set Env vars
        $_ENV['ENV_VALUE1'] = "should not be applied";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";


        $test = new EnvVarSettings();
        $schema = $this->schemaManager->getSettingsMetadata(EnvVarSettings::class);

        $this->service->applyEnvVariableOverwrites($test, $schema);

        //Afterwards the settings values should be set to the env vars, only on the variables where the env var is in Overwrite mode
        $this->assertEquals('default', $test->value1); //not applied here
        $this->assertTrue($test->value2);
        $this->assertSame(123.4, $test->value3);
        $this->assertTrue($test->value4);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

    public function testUndoEnvVariableOverwrites(): void
    {
        $schema = $this->schemaManager->getSettingsMetadata(EnvVarSettings::class);

        $old_data = [
            'value1' => "stored",
            //'value2' => false, //not set
            'value3' => -10.0,
            'value4' => null,
        ];

        $data = [
            'value1' => "stored_new",
            'value3' => -123,
            'value4' => true,
        ];

        // No env vars yet, so we assume that no env vars were overwritten -> nothing should change
        $this->assertSame($data, $this->service->undoEnvVariableOverwrites($schema, $data, $old_data));

        //Set Env vars
        $_ENV['ENV_VALUE1'] = "should not be applied";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";

        // Now we have env vars, so we assume that they were overwritten -> the old data should be restored
        $modified = $this->service->undoEnvVariableOverwrites($schema, $data, $old_data);

        //For value1 the env mode is INITIAL and not OVERWRITE, so it should not be changed
        $this->assertSame($data['value1'], $modified['value1']);

        //Value2 is not defined in the old data, so it should be removed
        $this->assertFalse(isset($modified['value2']));
        //For value2 and value3 the mode is OVERWRITE, so the old data should be restored
        $this->assertSame($old_data['value3'], $modified['value3']);

        //For value4 the mode is OVERWRITE_PERSIST, so the modified data should be returned so that it gets persisted
        $this->assertSame($data['value4'], $modified['value4']);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

    public function testCacheing(): void
    {
        $_ENV['ENV_VALUE1'] = "false";
        $_ENV['ENV_VALUE2'] = 'envVar Override';

        $cacheService = self::getContainer()->get(SettingsCacheInterface::class);
        $metadata = $this->schemaManager->getSettingsMetadata(CacheableSettings::class);

        //We start with an empty cache
        $this->assertFalse($cacheService->hasData($metadata));

        //Prepare the storage adapter with some dummy data to migrate
        $this->storageAdapter->save($metadata->getStorageKey(), [
            '$META$' => [
                'version' => 1,
            ],
            'string' => 'stored',
        ]);

        //Create a new settings object
        $test = new CacheableSettings();
        //We have initial data from somewhere else
        $test->dateTime = new \DateTimeImmutable('2024-01-01');

        //Hydrate the settings object. This should work without any errors.
        $test = $this->service->hydrate($test, $metadata);

        //The cache should now contain the data
        $this->assertTrue($cacheService->hasData($metadata));

        //Therefore if we apply it to a new object, it should be the same
        $other = new CacheableSettings();
        $other = $cacheService->applyData($metadata, $other);
        $this->assertSame('envVar Override', $other->string);
        $this->assertFalse($other->bool);
        $this->assertEquals($test->dateTime, $other->dateTime);

        //If we change the data in the storageAdapter, outside of the hydrator, it should be ignored as we use the cached version
        $this->storageAdapter->save($metadata->getStorageKey(), [
            '$META$' => [
                'version' => 1,
            ],
            'array' => ['changed' => 'array'],
        ]);

        $other = new CacheableSettings();
        $other = $this->service->hydrate($other, $metadata);
        //The array must not be changed
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $other->array);

        //If we say that the cache should be ignored, the new data should be applied
        $other = new CacheableSettings();
        $other = $this->service->hydrate($other, $metadata, true);
        $this->assertEquals(['changed' => 'array'], $other->array);

        //When we perform a change on the object, the cache should be invalidated
        $test->array = ['new' => 'array'];
        $test = $this->service->persist($test, $metadata);

        //The cache must now contain the new data
        $other = new CacheableSettings();
        $other = $cacheService->applyData($metadata, $other);
        $this->assertEquals(['new' => 'array'], $other->array);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2']);

        //Clear the cache
        $cacheService->invalidateData($metadata);
    }
}
