<?php


/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
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

use Jbtronics\SettingsBundle\Manager\SettingsResetter;
use Jbtronics\SettingsBundle\Manager\SettingsResetterInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManager;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SettingsResetterTest extends KernelTestCase
{

    private SettingsResetterInterface $service;
    private MetadataManagerInterface $metadataManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsResetterInterface::class);
        $this->metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
    }

    public function testResetSettingsOnlyProperties(): void
    {
        $settings = new SimpleSettings();
        $settings->setValue1('changed');
        $settings->setValue2(42);
        $settings->setValue3(true);

        //Create a schema with all properties
        $schema = new SettingsMetadata(
            className: get_class($settings),
            parameterMetadata: [
                new ParameterMetadata(className: get_class($settings), propertyName: 'value1', type: StringType::class, nullable: true),
                new ParameterMetadata(className: get_class($settings), propertyName: 'value2', type: IntType::class, nullable: true),
                //This property is deliberately left out, and it should not be resetted
                //new ParameterSchema(className: get_class($settings), propertyName: 'value3', type: BoolType::class),
            ],
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test'
        );

        $this->service->resetSettings($settings, $schema);

        $this->assertEquals('default', $settings->getValue1());
        $this->assertNull($settings->getValue2());
        //This property must not been resetted
        $this->assertTrue($settings->getValue3());
    }

    public function testResetSettingsResettableInterface(): void
    {
        $settings = new class implements ResettableSettingsInterface {

            public string $value1 = 'default';
            public int $value2;
            public ?bool $value3;

            public bool $wasResetted = false;

            public function resetToDefaultValues(): void
            {
                //Set the default value of value 2
                $this->value2 = 42;

                //Mark that this class was resetted
                $this->wasResetted = true;
            }
        };


        $settings->value1 = 'changed';
        $settings->value2 = 42;
        $settings->value3 = true;

        //Create a schema with all properties
        $schema = new SettingsMetadata(
            className: get_class($settings),
            parameterMetadata: [
                new ParameterMetadata(className: get_class($settings), propertyName: 'value1', type: StringType::class, nullable: true),
                new ParameterMetadata(className: get_class($settings), propertyName: 'value2', type: IntType::class,  nullable: true),
                new ParameterMetadata(className: get_class($settings), propertyName: 'value3', type: BoolType::class, nullable: true),
            ],
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test'
        );

        $this->service->resetSettings($settings, $schema);

        //The reset method should have been called
        $this->assertTrue($settings->wasResetted);

        //And the properties should have been resetted
        $this->assertEquals('default', $settings->value1);
        $this->assertSame(42, $settings->value2);
        //This property must be null now, as the not explicitly set value of a nullable property is null
        $this->assertNull($settings->value3);
    }

    public function testResetSettingsNotResetableProperty(): void
    {
        //If we encounter a property without a default value and which is not nullable, we throw an exception
        $this->expectException(LogicException::class);

        $settings = new class {
            public string $value1;

            public function resetToDefaultValues(): void
            {
                //Set the default value of value 2
                $this->value2 = 42;

                //Mark that this class was resetted
                $this->wasResetted = true;
            }
        };

        $schema = new SettingsMetadata(
            className: get_class($settings),
            parameterMetadata: [
                new ParameterMetadata(className: get_class($settings), propertyName: 'value1', type: StringType::class, nullable: true),
            ],
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test'
        );

        $this->service->resetSettings($settings, $schema);
    }

    public function testNewInstanceOnlyProperties(): void
    {
        $metadata = $this->metadataManager->getSettingsMetadata(SimpleSettings::class);
        $settings = $this->service->newInstance($metadata);

        $this->assertEquals('default', $settings->getValue1());
        $this->assertNull($settings->getValue2());
        $this->assertFalse($settings->getValue3());
    }

    public function testNewInstanceResettableInterface(): void
    {
        $settings = new class implements ResettableSettingsInterface {

            public string $value1 = 'default';
            public int $value2;
            public ?bool $value3;

            public bool $wasResetted = false;

            public function resetToDefaultValues(): void
            {
                //Set the default value of value 2
                $this->value2 = 42;

                //Mark that this class was resetted
                $this->wasResetted = true;
            }
        };

        //Create a schema with all properties
        $schema = new SettingsMetadata(
            className: get_class($settings),
            parameterMetadata: [
                new ParameterMetadata(className: get_class($settings), propertyName: 'value1', type: StringType::class, nullable: true),
                new ParameterMetadata(className: get_class($settings), propertyName: 'value2', type: IntType::class,  nullable: true),
                new ParameterMetadata(className: get_class($settings), propertyName: 'value3', type: BoolType::class, nullable: true),
            ],
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test'
        );

        $settings = $this->service->newInstance($schema);

        //The reset method should have been called
        $this->assertTrue($settings->wasResetted);

        //And the properties should have been resetted
        $this->assertEquals('default', $settings->value1);
        $this->assertSame(42, $settings->value2);
    }

    public function testEnvVarsApplied(): void
    {
        //Set the environment variables
        $_ENV['ENV_VALUE1'] = 'new_default';
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'does not matter';

        /** @var SettingsMetadata $metadata */
        $metadata = $this->metadataManager->getSettingsMetadata(EnvVarSettings::class);

        $settings = $this->service->newInstance($metadata);

        //The environment variables should have overwritten the default values
        $this->assertEquals('new_default', $settings->value1);
        $this->assertTrue($settings->value2);
        $this->assertSame(123.4, $settings->value3);
        //On the other hand, the default value of value4 should not have been overwritten, as the environment variable is not set
        $this->assertFalse($settings->value4);

        //When we change the values, the reset method should reset them to the values from the environment variables
        $settings->value1 = 'changed';
        $settings->value2 = false;
        $settings->value3 = 42.0;

        $this->service->resetSettings($settings, $metadata);
        $this->assertEquals('new_default', $settings->value1);
        $this->assertTrue($settings->value2);
        $this->assertSame(123.4, $settings->value3);

        //Unset the environment variable to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

}
