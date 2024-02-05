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

namespace Jbtronics\SettingsBundle\Tests\Proxy;

use Jbtronics\SettingsBundle\Proxy\ProxyFactory;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\VarExporter\LazyObjectInterface;

class ProxyFactoryTest extends KernelTestCase
{

    /** @var ProxyFactory $proxyFactory  */
    private ProxyFactoryInterface $proxyFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->proxyFactory = self::getContainer()->get(ProxyFactoryInterface::class);
        $this->assertInstanceOf(ProxyFactory::class, $this->proxyFactory);
    }

    public function testGenerateProxyClassFiles(): void
    {
        //Ensure that this method does not throw an exception
        $this->proxyFactory->generateProxyClassFiles([SimpleSettings::class]);

        //Ensure that the proxy class file was generated
        $this->assertFileExists($this->proxyFactory->getProxyFilename(SimpleSettings::class));
    }

    public function testCreateProxy(): void
    {
        $initializer = function () {
            $tmp = new SimpleSettings();
            $tmp->setValue1('Initialized');
            return $tmp;
        };

        /** @var LazyObjectInterface&SimpleSettings&SettingsProxyInterface $proxy */
        $proxy = $this->proxyFactory->createProxy(SimpleSettings::class, $initializer);
        $this->assertInstanceOf(SimpleSettings::class, $proxy);
        $this->assertInstanceOf(SettingsProxyInterface::class, $proxy);

        //The proxy should also be instance of the LazyObjectInterface
        $this->assertInstanceOf(LazyObjectInterface::class, $proxy);
        //Directly after creation, the proxy should not be initialized
        $this->assertFalse($proxy->isLazyObjectInitialized());

        //When we access a property, the proxy should be initialized
        $this->assertEquals('Initialized', $proxy->getValue1());
        $this->assertTrue($proxy->isLazyObjectInitialized());
    }
}
