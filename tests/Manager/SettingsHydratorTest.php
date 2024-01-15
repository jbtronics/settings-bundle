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

use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsHydratorTest extends WebTestCase
{
    private SettingsHydrator $service;
    private MetadataManagerInterface $schemaManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsHydratorInterface::class);
        $this->schemaManager = self::getContainer()->get(MetadataManagerInterface::class);
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

}
