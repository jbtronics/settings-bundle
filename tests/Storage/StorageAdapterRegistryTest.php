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

namespace Jbtronics\SettingsBundle\Tests\Storage;

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistry;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
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
