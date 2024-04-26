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

use Jbtronics\SettingsBundle\Exception\ParameterDataNotCloneableException;
use Jbtronics\SettingsBundle\Manager\SettingsClonerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsResetterInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\GuessableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\MergeableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\NonCloneableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SettingsClonerTest extends KernelTestCase
{

    private SettingsClonerInterface $service;
    private SettingsManagerInterface $settingsManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->service = self::getContainer()->get(SettingsClonerInterface::class);
        $this->settingsManager = self::getContainer()->get(SettingsManagerInterface::class);
    }

    public function testCreateCloneSimple(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->settingsManager->get(SimpleSettings::class);
        $settings->setValue1('new value');
        $settings->setValue2(1111);

        $clone = $this->service->createClone($settings);

        //Assert that a new instance was created
        $this->assertNotSame($settings, $clone);

        //But the values are the same
        $this->assertEquals($settings->getValue1(), $clone->getValue1());
        $this->assertEquals($settings->getValue2(), $clone->getValue2());
    }

    public function testCreateCloneEnums(): void
    {
        /** @var GuessableSettings $settings */
        $settings = $this->settingsManager->get(GuessableSettings::class);
        $settings->enum = TestEnum::FOO;
        $settings->stdClass = new \stdClass();
        $settings->stdClass->foo = 'bar';

        $clone = $this->service->createClone($settings);

        //Assert that a new instance was created
        $this->assertNotSame($settings, $clone);

        //But the values are the same
        $this->assertEquals($settings->enum, $clone->enum);
    }

    public function testCreateCloneEmbeddeds(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->settingsManager->get(EmbedSettings::class);

        //Modify some values
        $settings->simpleSettings->setValue1('new value');
        $settings->simpleSettings->setValue2(1111);
        $settings->circularSettings->bool = false;
        $settings->circularSettings->guessableSettings->int = 1234;

        /** @var EmbedSettings $clone */
        $clone = $this->service->createClone($settings);

        //Assert that a new instance was created for the top level settings and all their embeds
        $this->assertNotSame($settings, $clone);
        $this->assertNotSame($settings->simpleSettings, $clone->simpleSettings);
        $this->assertNotSame($settings->circularSettings, $clone->circularSettings);
        $this->assertNotSame($settings->circularSettings->guessableSettings, $clone->circularSettings->guessableSettings);

        //Assert that the circular reference is set to the correct instance (the clone of the top level settings)
        $this->assertSame($clone, $clone->circularSettings->embeddedSettings);

        //The values should be the same
        $this->assertEquals($settings->simpleSettings->getValue1(), $clone->simpleSettings->getValue1());
        $this->assertEquals($settings->simpleSettings->getValue2(), $clone->simpleSettings->getValue2());
        $this->assertEquals($settings->circularSettings->bool, $clone->circularSettings->bool);
        $this->assertEquals($settings->circularSettings->guessableSettings->int, $clone->circularSettings->guessableSettings->int);
    }

    public function testCreateCloneAfterMergeFnCalled(): void
    {
        /** @var MergeableSettings $settings */
        $settings = $this->settingsManager->get(MergeableSettings::class);

        $clone = $this->service->createClone($settings);

        //Ensure that new instances were created
        $this->assertNotSame($settings, $clone);
        $this->assertNotSame($settings->dateTime1, $clone->dateTime1);
        //Datetime2 must be the same instance, as it was marked as not cloneable
        $this->assertSame($settings->dateTime2, $clone->dateTime2);

        //Ensure that the afterSettingsClone method was called, with the original settings instance as argument
        $this->assertSame($settings, $clone->cloneCalled);
    }

    public function testMergeCopySimple(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->settingsManager->get(SimpleSettings::class);

        $clone = $this->service->createClone($settings);

        $clone->setValue1('new value');
        $clone->setValue2(1111);

        //Merge the changes back into the original instance
        $ret = $this->service->mergeCopy($clone, $settings);

        //Ensure that the return value is the same instance as the original settings
        $this->assertSame($settings, $ret);

        //Ensure that the values were merged
        $this->assertEquals($clone->getValue1(), $settings->getValue1());
        $this->assertEquals($clone->getValue2(), $settings->getValue2());
    }

    public function testMergeCopyEnum(): void
    {
        /** @var GuessableSettings $settings */
        $settings = $this->settingsManager->get(GuessableSettings::class);
        $settings->stdClass = new \stdClass();

        $clone = $this->service->createClone($settings);

        $clone->enum = TestEnum::BAR;
        $clone->stdClass = new \stdClass();
        $clone->stdClass->foo = 'bar';

        $ret = $this->service->mergeCopy($clone, $settings);
        $this->assertSame($settings, $ret);

        $this->assertEquals($clone->enum, $settings->enum);
        //Ensure that the stdClass property was not merged, as it is no parameter of the settings class
        $this->assertNotEquals($clone->stdClass, $settings->stdClass);
    }

    public function testMergeCopyAfterMergeFnCalled(): void
    {
        /** @var MergeableSettings $settings */
        $settings = $this->settingsManager->get(MergeableSettings::class);

        $clone = $this->service->createClone($settings);

        $clone->dateTime1 = new \DateTime('2024-01-01');
        $clone->dateTime2 = new \DateTime('2024-01-02');

        $ret = $this->service->mergeCopy($clone, $settings);

        $this->assertSame($settings, $ret);

        //Assert that a new instance was created for the dateTime1 property
        $this->assertNotSame($clone->dateTime1, $settings->dateTime1);
        //Datetime2 must be the same instance, as it was marked as not cloneable and then the old instance should be kept
        $this->assertSame($clone->dateTime2, $settings->dateTime2);

        //Ensure that the afterSettingsMerge method was called on the original, with the clone instance as argument
        $this->assertSame($clone, $settings->mergeCalled);
    }

    public function testMergeCopyEmbeddedsNonRecursive(): void
    {
        /**
         * @var EmbedSettings $settings
         */
        $settings = $this->settingsManager->get(EmbedSettings::class);

        /** @var EmbedSettings $clone */
        $clone = $this->service->createClone($settings);

        $clone->simpleSettings->setValue1('new value');
        $clone->simpleSettings->setValue2(1111);
        $clone->bool = false;

        $ret = $this->service->mergeCopy($clone, $settings, false);

        $this->assertSame($settings, $ret);

        //Ensure that the top level settings were merged
        $this->assertSame($clone->bool, $settings->bool);

        //Ensure that the embedded settings were not merged
        $this->assertNotEquals($clone->simpleSettings->getValue1(), $settings->simpleSettings->getValue1());
        $this->assertNotEquals($clone->simpleSettings->getValue2(), $settings->simpleSettings->getValue2());
    }

    public function testMergeCopyEmbeddedRecursive(): void
    {
        /**
         * @var EmbedSettings $settings
         */
        $settings = $this->settingsManager->get(EmbedSettings::class);

        /** @var EmbedSettings $clone */
        $clone = $this->service->createClone($settings);

        $clone->simpleSettings->setValue1('new value');
        $clone->simpleSettings->setValue2(1111);
        $clone->bool = false;
        $clone->circularSettings->bool = false;
        $clone->circularSettings->guessableSettings->int = 1234;

        //Recursive should be the default, so we do not need to set it
        $ret = $this->service->mergeCopy($clone, $settings);

        $this->assertSame($settings, $ret);

        //Ensure that the top level settings were merged
        $this->assertSame($clone->bool, $settings->bool);

        //Ensure that the embedded settings were merged
        $this->assertEquals($clone->simpleSettings->getValue1(), $settings->simpleSettings->getValue1());
        $this->assertEquals($clone->simpleSettings->getValue2(), $settings->simpleSettings->getValue2());

        //Ensure that the circular reference is set to the correct instance (the original settings instance)
        $this->assertSame($settings, $settings->circularSettings->embeddedSettings);

        //Ensure that the embedded settings of the circular settings were merged
        $this->assertSame($clone->circularSettings->bool, $settings->circularSettings->bool);
        $this->assertSame($clone->circularSettings->guessableSettings->int, $settings->circularSettings->guessableSettings->int);
    }

    public function testMergeCopyInvalidTypes(): void
    {
        $obj1 = $this->settingsManager->get(SimpleSettings::class);
        $obj2 = $this->settingsManager->get(GuessableSettings::class);

        $this->expectException(\InvalidArgumentException::class);

        //Must fail, as the classes are not the same
        $this->service->mergeCopy($obj1, $obj2);
    }

    public function testCreateCloneNonCloneableProperty(): void
    {
        $obj = new NonCloneableSettings();

        //A Not clonable exception must be thrown
        $this->expectException(ParameterDataNotCloneableException::class);

        $clone = $this->service->createClone($obj);
    }

    public function testMergeCloneNonCloneableProperty(): void
    {
        $obj1 = new NonCloneableSettings();
        $obj2 = new NonCloneableSettings();

        //The merge must also throw such an exception
        $this->expectException(ParameterDataNotCloneableException::class);

        $this->service->mergeCopy($obj1, $obj2);
    }
}
