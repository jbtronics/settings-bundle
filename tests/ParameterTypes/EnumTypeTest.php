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

use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use PHPUnit\Framework\TestCase;

class EnumTypeTest extends TestCase
{

    private EnumType $enumType;

    public function setUp(): void
    {
        $this->enumType = new EnumType();
    }

    public function testConvertNormalizedToPHP(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => TestEnum::class]);

        $this->assertEquals(TestEnum::FOO, $this->enumType->convertNormalizedToPHP(1, $metadata));
        $this->assertEquals(TestEnum::BAR, $this->enumType->convertNormalizedToPHP(2, $metadata));
        $this->assertEquals(TestEnum::BAZ, $this->enumType->convertNormalizedToPHP(3, $metadata));

        $this->assertNull($this->enumType->convertNormalizedToPHP(null, $metadata));
    }

    public function testConvertPHPToNormalized(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => TestEnum::class]);

        $this->assertEquals(1, $this->enumType->convertPHPToNormalized(TestEnum::FOO, $metadata));
        $this->assertEquals(2, $this->enumType->convertPHPToNormalized(TestEnum::BAR, $metadata));
        $this->assertEquals(3, $this->enumType->convertPHPToNormalized(TestEnum::BAZ, $metadata));

        $this->assertNull($this->enumType->convertPHPToNormalized(null, $metadata));
    }

    public function testGetFormType()
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $this->assertEquals(\Symfony\Component\Form\Extension\Core\Type\EnumType::class, $this->enumType->getFormType($metadata));
    }

    public function testConfigureFormOptions()
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => TestEnum::class]);

        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $this->enumType->configureFormOptions($resolver, $metadata);

        $this->assertEquals(['class' => TestEnum::class], $resolver->resolve());
    }
}
