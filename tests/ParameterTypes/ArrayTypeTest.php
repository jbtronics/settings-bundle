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
use Symfony\Component\TypeInfo\Type;

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

    /**
     * Runs both conversion directions with a sub-parameter type, which captures the metadata it receives
     * @return ParameterMetadata[]
     */
    private function captureSubParameterMetadata(?Type $phpType, array $value): array
    {
        $captured = [];

        $subType = $this->createMock(ParameterTypeInterface::class);
        $subType->method('convertPHPToNormalized')->willReturnCallback(function ($value, ParameterMetadata $metadata) use (&$captured) {
            $captured[] = $metadata;
            return null;
        });
        $subType->method('convertNormalizedToPHP')->willReturnCallback(function ($value, ParameterMetadata $metadata) use (&$captured) {
            $captured[] = $metadata;
            return $value;
        });

        $registry = $this->createMock(ParameterTypeRegistryInterface::class);
        $registry->method('getParameterType')->willReturn($subType);

        $arrayType = new ArrayType($registry);

        $metadata = new ParameterMetadata(
            className: self::class,
            propertyName: 'test',
            type: ArrayType::class,
            nullable: false,
            options: [
                'type' => StringType::class,
            ],
            phpType: $phpType,
        );

        $arrayType->convertPHPToNormalized($value, $metadata);
        $arrayType->convertNormalizedToPHP($value, $metadata);

        $this->assertCount(count($value) * 2, $captured);

        return $captured;
    }

    public function testSubMetadataPHPTypeFromList(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::list(Type::string()), ['foo', 'bar']);

        foreach ($captured as $subMetadata) {
            $this->assertEquals(Type::string(), $subMetadata->getPHPType());
        }
    }

    public function testSubMetadataPHPTypeFromAssociativeArray(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::array(Type::enum(TestEnum::class), Type::string()), ['a' => 'foo']);

        foreach ($captured as $subMetadata) {
            $this->assertEquals(Type::enum(TestEnum::class), $subMetadata->getPHPType());
            $this->assertSame('test[a]', $subMetadata->getPropertyName());
        }
    }

    public function testSubMetadataPHPTypeFromNullableArray(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::nullable(Type::list(Type::int())), [1, 2]);

        foreach ($captured as $subMetadata) {
            $this->assertEquals(Type::int(), $subMetadata->getPHPType());
        }
    }

    public function testSubMetadataPHPTypeFromNullableValueType(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::list(Type::nullable(Type::string())), ['foo', null]);

        foreach ($captured as $subMetadata) {
            $this->assertEquals(Type::nullable(Type::string()), $subMetadata->getPHPType());
        }
    }

    public function testSubMetadataPHPTypeFromNestedArray(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::list(Type::list(Type::string())), [['foo']]);

        foreach ($captured as $subMetadata) {
            $this->assertEquals(Type::list(Type::string()), $subMetadata->getPHPType());
        }
    }

    public function testSubMetadataPHPTypeWithoutPHPType(): void
    {
        $captured = $this->captureSubParameterMetadata(null, ['foo']);

        foreach ($captured as $subMetadata) {
            $this->assertNull($subMetadata->getPHPType());
        }
    }

    public function testSubMetadataPHPTypeWithNonCollectionType(): void
    {
        $captured = $this->captureSubParameterMetadata(Type::mixed(), ['foo']);

        foreach ($captured as $subMetadata) {
            $this->assertNull($subMetadata->getPHPType());
        }
    }
}
