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