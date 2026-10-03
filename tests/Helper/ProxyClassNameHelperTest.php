<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Helper;

use Jbtronics\SettingsBundle\Helper\ProxyClassNameHelper;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;

class ProxyClassNameHelperTest extends TestCase
{
    public function testResolveEffectiveClassForNormalObject(): void
    {
        $this->assertSame(SimpleSettings::class, ProxyClassNameHelper::resolveEffectiveClass(new SimpleSettings()));
    }

    public function testResolveEffectiveClassForLegacyProxy(): void
    {
        //Legacy proxies (pre-PHP 8.4) are subclasses of the settings class, implementing the SettingsProxyInterface
        $proxy = new class extends SimpleSettings implements SettingsProxyInterface {
            public function isLazyObjectInitialized(bool $partial = false): bool
            {
                return true;
            }

            public function initializeLazyObject(): object
            {
                return $this;
            }

            public function resetLazyObject(): bool
            {
                return false;
            }
        };

        $this->assertSame(SimpleSettings::class, ProxyClassNameHelper::resolveEffectiveClass($proxy));
    }
}
