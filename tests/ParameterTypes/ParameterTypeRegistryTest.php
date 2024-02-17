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

namespace Jbtronics\SettingsBundle\Tests\ParameterTypes;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistry;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterTypeRegistryTest extends KernelTestCase
{
    private ParameterTypeRegistryInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(ParameterTypeRegistryInterface::class);
    }

    public function builtInTypesDataProvider(): array
    {
        return [
            [IntType::class],
            [BoolType::class],
            [StringType::class]
        ];
    }

    /**
     * @dataProvider builtInTypesDataProvider
     */
    public function testGetParameterType(string $class): void
    {
        $this->assertInstanceOf(ParameterTypeRegistryInterface::class, $this->service);
        $this->assertInstanceOf(ParameterTypeRegistry::class, $this->service);

        $type = $this->service->getParameterType($class);
        $this->assertInstanceOf($class, $type);
    }

    public function testGetRegisteredParameterTypes(): void
    {
        $types = $this->service->getRegisteredParameterTypes();
        $this->assertNotEmpty($types);
        $this->assertContains(IntType::class, $types);
    }
}
