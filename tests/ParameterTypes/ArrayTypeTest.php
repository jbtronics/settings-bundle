<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
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

use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\ParameterTypes\ArrayType;
use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArrayTypeTest extends WebTestCase
{

    private ParameterTypeRegistryInterface $parameterTypeRegistry;

    private ArrayType $arrayType;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->parameterTypeRegistry = self::getContainer()->get(ParameterTypeRegistryInterface::class);
        $this->arrayType = new ArrayType($this->parameterTypeRegistry);
    }

    public function testSimpleType(): void
    {
        $array = ['foo', 'bar', 'baz'];

        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(false);
        $metadata->method('getOptions')->willReturn([
            'type' => StringType::class
        ]);

        $normalized = $this->arrayType->convertPHPToNormalized($array, $metadata);

        //For this simple type the normalized value should be the same as the original value
        $this->assertEquals($array, $normalized);

        //Unserialiazation should also work fine
        $php = $this->arrayType->convertNormalizedToPHP($normalized, $metadata);
        $this->assertEquals($array, $php);
    }

    public function testAssocativeArray(): void
    {
        $array = ['foo' => 'bar', 'baz' => 'qux', 5 => "test"];

        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(false);
        $metadata->method('getOptions')->willReturn([
            'type' => StringType::class
        ]);

        $normalized = $this->arrayType->convertPHPToNormalized($array, $metadata);

        //For assocative arrays the normalized value should be the same as the original value
        $this->assertEquals($array, $normalized);

        //Unserialiazation should also work fine
        $php = $this->arrayType->convertNormalizedToPHP($normalized, $metadata);
        $this->assertEquals($array, $php);
    }

    public function testNullOnNullable(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(true);
        $metadata->method('getOptions')->willReturn([
            'type' => StringType::class
        ]);

        $normalized = $this->arrayType->convertPHPToNormalized(null, $metadata);

        //For null values the normalized value should be null
        $this->assertNull($normalized);

        //Unserialiazation should also work fine
        $php = $this->arrayType->convertNormalizedToPHP($normalized, $metadata);
        $this->assertNull($php);
    }

    public function testNullOnNonNullable(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(false);
        $metadata->method('getOptions')->willReturn([
            'type' => StringType::class
        ]);

        //For normalized null, the PHP value should be an empty array
        $php = $this->arrayType->convertNormalizedToPHP(null, $metadata);
        $this->assertEquals([], $php);
    }

    public function testEnumType(): void
    {
        $array = [TestEnum::BAZ, TestEnum::FOO, TestEnum::BAR];

        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(false);
        $metadata->method('getOptions')->willReturn([
            'type' => EnumType::class,
            'options' => [
                'class' => TestEnum::class,
            ],
            'nullable' => true
        ]);

        $normalized = $this->arrayType->convertPHPToNormalized($array, $metadata);

        //It should return an array of normalized values
        $this->assertEquals([3, 1, 2], $normalized);

        //Unserialiazation should also work fine
        $php = $this->arrayType->convertNormalizedToPHP($normalized, $metadata);
        $this->assertEquals($array, $php);
    }

    public function testNestedArray(): void
    {
        $array = [
            'foo' => ['bar', 'baz'],
            'qux' => ['quux', 'corge']
        ];

        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getClassName')->willReturn(self::class);
        $metadata->method('getPropertyName')->willReturn('test');
        $metadata->method('isNullable')->willReturn(false);
        $metadata->method('getOptions')->willReturn([
            'type' => ArrayType::class,
            'options' => [
                'type' => StringType::class
            ]
        ]);

        $normalized = $this->arrayType->convertPHPToNormalized($array, $metadata);

        //It should return an array of normalized values
        $this->assertEquals([
            'foo' => ['bar', 'baz'],
            'qux' => ['quux', 'corge']
        ], $normalized);

        //Unserialiazation should also work fine
        $php = $this->arrayType->convertNormalizedToPHP($normalized, $metadata);
        $this->assertEquals($array, $php);
    }
}
