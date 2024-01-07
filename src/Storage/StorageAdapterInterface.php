<?php


/*
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