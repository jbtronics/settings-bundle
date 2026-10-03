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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Tests\Profiler;

use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests that the profiler toolbar and panel of the settings collector can be rendered.
 */
class SettingsProfilerPanelTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    private function createProfileToken(): string
    {
        $this->client->enableProfiler();
        $this->client->request('GET', '/profiler_test');
        $this->assertResponseIsSuccessful();

        $profile = $this->client->getProfile();
        $this->assertNotFalse($profile);
        $this->assertTrue($profile->hasCollector(SettingsCollector::class));

        return $profile->getToken();
    }

    public function testCollectorIsRegistered(): void
    {
        $this->client->enableProfiler();
        $this->client->request('GET', '/profiler_test');

        /** @var SettingsCollector $collector */
        $collector = $this->client->getProfile()->getCollector(SettingsCollector::class);
        $this->assertInstanceOf(SettingsCollector::class, $collector);
        $this->assertContains(SimpleSettings::class, $collector->getSettingsClasses());
    }

    public function testRenderToolbar(): void
    {
        $token = $this->createProfileToken();

        $this->client->request('GET', '/_wdt/'.$token);
        $this->assertResponseIsSuccessful();

        //The collector name contains backslashes, so we can not use it in a class selector
        $toolbarBlock = '.sf-toolbar-block:has(a[href*="panel='.rawurlencode(SettingsCollector::class).'"])';
        $this->assertSelectorExists($toolbarBlock);
        $this->assertSelectorTextContains($toolbarBlock, 'Number of settings classes');
    }

    public function testRenderPanel(): void
    {
        $token = $this->createProfileToken();

        $this->client->request('GET', '/_profiler/'.$token, ['panel' => SettingsCollector::class]);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('#collector-content h2', 'Settings bundle');

        //The settings classes must be listed in the tree menu
        $this->assertSelectorExists('#tree-menu [data-tab-target-id="'.$this->getClassKey(SimpleSettings::class).'-details"]');
        $this->assertSelectorExists('#tree-menu [data-tab-target-id="'.$this->getClassKey(EmbedSettings::class).'-details"]');

        //The details of the settings class must be rendered
        $simpleKey = '#'.$this->getClassKey(SimpleSettings::class);
        $this->assertSelectorTextContains($simpleKey.'-details', 'Simple Settings');
        $this->assertSelectorTextContains($simpleKey.'-details', 'InMemoryStorageAdapter');

        //The details of the parameters must be rendered (they are keyed by the parameter name)
        $this->assertSelectorTextContains($simpleKey.'-value1-details', 'StringType');
        $this->assertSelectorTextContains($simpleKey.'-value1-details', 'default');
        $this->assertSelectorTextContains($simpleKey.'-property2-details', 'value2');
        $this->assertSelectorTextContains($simpleKey.'-property2-details', 'IntType');

        //The details of embedded settings must be rendered
        $embedKey = '#'.$this->getClassKey(EmbedSettings::class);
        $this->assertSelectorTextContains($embedKey.'-embedded-simpleSettings-details', 'SimpleSettings');
        $this->assertSelectorTextContains($embedKey.'-embedded-circularSettings-details', 'Override Label');
    }

    /**
     * Returns the key used in the HTML ids for the given class (equivalent to the class_key macro in the template).
     */
    private function getClassKey(string $class): string
    {
        return 'class-'.strtolower(str_replace(['\\', '/', '.'], '_', $class));
    }
}
