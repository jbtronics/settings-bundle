<?php

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class SettingsRegistryTest extends TestCase
{
    public function testGetConfigClasses(): void
    {
        $configurationRegistry = new SettingsRegistry(
            [
                __DIR__.'/../TestApplication/src/Settings/',
            ],
            new NullAdapter(),
            false,
        );

        $this->assertEquals([
            SimpleSettings::class
        ], $configurationRegistry->getSettingsClasses());
    }
}
