<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Proxy;

use Jbtronics\SettingsBundle\Proxy\LazyObjectHelper;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LazyObjectHelperTest extends KernelTestCase
{
    public function testIsUninitializedLazyObject(): void
    {
        self::bootKernel();
        $proxyFactory = self::getContainer()->get(ProxyFactoryInterface::class);

        //Depending on the PHP version, this is either a native lazy object or a legacy proxy
        $proxy = $proxyFactory->createProxy(SimpleSettings::class, function (SimpleSettings $instance) {
            $instance->setValue1('initialized');
        });

        $this->assertTrue(LazyObjectHelper::isUninitializedLazyObject($proxy));

        //Accessing the proxy initializes it
        $this->assertSame('initialized', $proxy->getValue1());
        $this->assertFalse(LazyObjectHelper::isUninitializedLazyObject($proxy));

        //Normal objects are no uninitialized lazy objects
        $this->assertFalse(LazyObjectHelper::isUninitializedLazyObject(new SimpleSettings()));
    }
}
