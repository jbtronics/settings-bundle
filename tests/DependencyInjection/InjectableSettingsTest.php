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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Tests\DependencyInjection;

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Service\InjectableSettingsTestService;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InjectableSettingsTest extends KernelTestCase
{
    private readonly InjectableSettingsTestService $injectableSettingsTestService;
    private readonly SimpleSettings $simpleSettingsAsService;

    private readonly SettingsManagerInterface $settingsManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->settingsManager = self::getContainer()->get(SettingsManagerInterface::class);
        $this->injectableSettingsTestService = self::getContainer()->get(InjectableSettingsTestService::class);
        $this->simpleSettingsAsService = self::getContainer()->get(SimpleSettings::class);
    }

    public function testInjection(): void
    {
        //The instance injected via the service container should be the same as the one injected via the constructor
        $injectedInstance = $this->injectableSettingsTestService->simpleSettings;

        //The injected Instance should be lazy loaded
        $this->assertInstanceOf(SettingsProxyInterface::class, $injectedInstance);

        $this->assertSame($this->simpleSettingsAsService, $injectedInstance);

        //And the same as the one retrieved from the settings manager
        /** @var SimpleSettings $fromManager */
        $fromManager = $this->settingsManager->get(SimpleSettings::class);
        $this->assertSame($injectedInstance, $fromManager);

        //Ensure that we can change the settings and the changes are reflected in the injected instance
        $fromManager->setValue1('changed value');

        $this->assertSame('changed value', $injectedInstance->getValue1());
    }
}