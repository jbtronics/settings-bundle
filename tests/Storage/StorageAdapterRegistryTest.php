<?php

namespace Jbtronics\SettingsBundle\Tests\Storage;

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistry;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StorageAdapterRegistryTest extends KernelTestCase
{

    private StorageAdapterRegistryInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(StorageAdapterRegistryInterface::class);
    }

    public function builtInTypesDataProvider(): array
    {
        return [
            [InMemoryStorageAdapter::class],
        ];
    }

    /**
     * @dataProvider builtInTypesDataProvider
     */
    public function testGetStorageAdapter(string $class): void
    {
        $this->assertInstanceOf(StorageAdapterRegistryInterface::class, $this->service);
        $this->assertInstanceOf(StorageAdapterRegistry::class, $this->service);

        $type = $this->service->getStorageAdapter($class);
        $this->assertInstanceOf($class, $type);
    }

    public function testGetRegisteredStorageAdapters(): void
    {
        $types = $this->service->getRegisteredStorageAdapters();
        $this->assertNotEmpty($types);
        $this->assertContains(InMemoryStorageAdapter::class, $types);
    }


}
