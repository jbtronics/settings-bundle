<?php

namespace Jbtronics\SettingsBundle\Storage;

/**
 * This class is used to store settings purely in memory.
 * This is useful for testing purposes.
 */
class InMemoryStorageAdapter implements StorageAdapterInterface
{
    private array $data = [];

    public function save(string $key, array $data): void
    {
        $this->data[$key] = $data;
    }

    public function load(string $key): ?array
    {
        return $this->data[$key] ?? null;
    }
}