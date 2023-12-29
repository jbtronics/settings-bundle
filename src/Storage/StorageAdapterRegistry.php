<?php

namespace Jbtronics\SettingsBundle\Storage;

use Symfony\Component\DependencyInjection\ServiceLocator;

class StorageAdapterRegistry implements StorageAdapterRegistryInterface
{
    public function __construct(
        private readonly ServiceLocator $locator
    )
    {
    }

    public function getStorageAdapter(string $className): StorageAdapterInterface
    {
        return $this->locator->get($className);
    }

    public function getRegisteredStorageAdapters(): array
    {
        return $this->locator->getProvidedServices();
    }
}