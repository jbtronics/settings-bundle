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

use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The functional/integration test for the SettingsManager
 */
class SettingsManagerTest extends KernelTestCase
{

    private SettingsManagerInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsManagerInterface::class);
    }

    public function testGet(): void
    {
        //Test if we can get the settings class by classname
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $this->assertInstanceOf(SimpleSettings::class, $settings);

        //Try to change a value
        $settings->setValue1('changed');

        //Test if we can get the settings class by short name
        $settings2 = $this->service->get('simple');
        $this->assertInstanceOf(SimpleSettings::class, $settings2);
        //Must be the same instance of the class
        $this->assertSame($settings, $settings2);

        //Test if the value is changed
        $this->assertEquals('changed', $settings2->getValue1());
    }

    public function testResetToDefaultValues(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $settings->setValue1('changed');

        $this->service->resetToDefaultValues($settings);

        $this->assertEquals('default', $settings->getValue1());
    }



    public function testSaveAndReload(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $settings->setValue1('changed');

        //Save the settings
        $this->service->save($settings);

        //Change the value again
        $settings->setValue1('changed again');

        //Reload the settings
        $this->service->reload($settings);

        //And the value should be the one, which we saved before
        $this->assertEquals('changed', $settings->getValue1());
    }
}
