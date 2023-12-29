<?php

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsHydrator;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SettingsHydratorTest extends WebTestCase
{
    private SettingsHydrator $service;
    private SchemaManagerInterface $schemaManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsHydratorInterface::class);
        $this->schemaManager = self::getContainer()->get(SchemaManagerInterface::class);
    }

    public function testToNormalizedRepresentation(): void
    {
        $test = new SimpleSettings();
        $test->setValue1('test');
        $test->setValue2(123);
        $test->setValue3(true);

        $schema = $this->schemaManager->getSchema(SimpleSettings::class);
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

        $schema = $this->schemaManager->getSchema(SimpleSettings::class);

        $this->service->applyNormalizedRepresentation($data, $test, $schema);

        //Assert the changed values
        $this->assertEquals('test', $test->getValue1());
        $this->assertEquals(123, $test->getValue2());
        //Value 3 must be left unchanged
        $this->assertFalse($test->getValue3());
    }

    public function testPersist()
    {
    }

    public function testHydrate()
    {
    }
}
