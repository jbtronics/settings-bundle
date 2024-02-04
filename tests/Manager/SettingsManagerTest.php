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
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CircularEmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * The functional/integration test for the SettingsManager
 */
class SettingsManagerTest extends KernelTestCase
{

    /** @var SettingsManager $service */
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

        //Save all must also work flawlessly
        $this->service->save();

        //Change the value again
        $settings->setValue1('changed again');

        //Reload the settings
        $this->service->reload($settings);

        //And the value should be the one, which we saved before
        $this->assertEquals('changed', $settings->getValue1());
    }

    public function testGetLazy(): void
    {
        $settings = $this->service->get(SimpleSettings::class, true);
        $this->assertInstanceOf(SimpleSettings::class, $settings);
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings);

        //Test if we can read the value
        $this->assertEquals('default', $settings->getValue1());

        //Test if we can change the value
        $settings->setValue1('changed');
        $this->assertEquals('changed', $settings->getValue1());

        //Test if we can save the settings
        $this->service->save($settings);
    }

    public function testReloadLazy(): void
    {
        $settings = $this->service->get(SimpleSettings::class, true);
        $this->assertInstanceOf(SimpleSettings::class, $settings);
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings);

        //Change the value of the settings
        $settings->setValue1('changed');
        $this->assertEquals('changed', $settings->getValue1());

        //Reloading the settings must work
        $this->service->reload($settings);

        //The value must be the default value again
        $this->assertEquals('default', $settings->getValue1());
    }

    public function testGetEmbedded(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $this->assertInstanceOf(EmbedSettings::class, $settings);

        $this->assertInstanceOf(SimpleSettings::class, $settings->simpleSettings);
        //Should be a lazy loaded instance
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings->simpleSettings);
        if ($settings->simpleSettings instanceof LazyObjectInterface) {
            $this->assertFalse($settings->simpleSettings->isLazyObjectInitialized());
        }

        //The embedded settings should be identical to the ones we get via the settings manager
        $this->assertSame($settings->simpleSettings, $this->service->get(SimpleSettings::class));

        //Test if we can retrieve the value via the embedded settings
        $this->assertEquals('default', $settings->simpleSettings->getValue1());


        if ($settings->simpleSettings instanceof LazyObjectInterface) {
            $this->assertTrue($settings->simpleSettings->isLazyObjectInitialized());
        }
    }

    public function testGetEmbeddedCircular(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $this->assertInstanceOf(EmbedSettings::class, $settings);
        $this->assertInstanceOf(CircularEmbedSettings::class, $settings->circularSettings);

        //The embedded settings should be identical to the ones we get via the settings manager
        $this->assertSame($settings->circularSettings, $this->service->get(CircularEmbedSettings::class));

        //Circular references should be resolved
        $this->assertSame($settings, $settings->circularSettings->embeddedSettings);

        //Test if we can retrieve the value via the embedded settings
        $this->assertEquals('default', $settings->circularSettings->embeddedSettings->simpleSettings->getValue1());
    }
}
