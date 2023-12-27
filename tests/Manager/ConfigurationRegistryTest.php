<?php

namespace Jbtronics\UserConfigBundle\Tests\Manager;

use Jbtronics\UserConfigBundle\Manager\ConfigurationRegistry;
use Jbtronics\UserConfigBundle\Tests\TestApplication\Config\SimpleConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class ConfigurationRegistryTest extends TestCase
{
    public function testGetConfigClasses(): void
    {
        $configurationRegistry = new ConfigurationRegistry(
            [
               __DIR__ . '/../TestApplication/src/Config/',
            ],
            new NullAdapter(),
            false,
        );

        $this->assertEquals([
            SimpleConfig::class
        ], $configurationRegistry->getConfigClasses());
    }
}
