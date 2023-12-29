<?php

namespace Jbtronics\SettingsBundle\Storage;

interface StorageAdapterRegistryInterface
{
    /**
     * Returns the parameter type service with the given class name.
     * @template T of StorageAdapterInterface
     * @param  string  $className
     * @phpstan-param class-string<T> $className
     * @return StorageAdapterInterface
     * @phpstan-return T
     */
    public function getStorageAdapter(string $className): StorageAdapterInterface;

    /**
     * Return an array of all registered storage adapters.
     * in the format ['parameter_type_service_name' => 'parameter_type_service_class']
     * @return array
     * @phpstan-return array<string, class-string<StorageAdapterInterface>>
     */
    public function getRegisteredStorageAdapters(): array;
}