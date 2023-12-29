<?php

namespace Jbtronics\SettingsBundle\Storage;

/**
 * This interface defines the methods, that a storage adapter must implement to be used by the SettingsManager.
 */
interface StorageAdapterInterface
{
    /**
     * Saves the given data to the storage adapter.
     * @param  string  $key The key to save the data under. This key is used to retrieve the data later. It can contain
     * any characters.
     * @param  array  $data An associative array of data to save. The data contains only JSON serializable values.
     * @phpstan-param array<string, array|string|bool|int|float|null> $data
     * @return void
     */
    public function save(string $key, array $data): void;

    /**
     * Retrieves the data from the storage adapter, that was saved under the given key before.
     * If no data was saved under the given key, null is returned.
     * @param  string  $key  The key to save the data under. This key is used to retrieve the data later. It can contain
     *  any characters.
     * @return array|null
     * @phpstan-return array<string, array|string|bool|int|float|null>|null
     */
    public function load(string $key): ?array;
}