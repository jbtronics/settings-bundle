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
use Jbtronics\SettingsBundle\ParameterTypes\FloatType;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FloatTypeTest extends TestCase
{

    private FloatType $floatType;

    public function setUp(): void
    {
        $this->floatType = new FloatType();
    }

    public function testConvertPHPToNormalized(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertEquals(1.0, $this->floatType->convertPHPToNormalized(1.0, $metadata));
        $this->assertNull($this->floatType->convertPHPToNormalized(null, $metadata));
    }

    public function testConvertPHPToNormalizedInvalidType(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->expectException(LogicException::class);
        $this->floatType->convertPHPToNormalized('1.0', $metadata);
    }

    public function testConvertNormalizedToPHP(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertSame(1.0, $this->floatType->convertNormalizedToPHP(1.0, $metadata));
        $this->assertNull($this->floatType->convertNormalizedToPHP(null, $metadata));

        // Conversion should be quite flexible
        $this->assertSame(1.0, $this->floatType->convertNormalizedToPHP('1.0', $metadata));
        $this->assertSame(1.0, $this->floatType->convertNormalizedToPHP(1, $metadata));
    }

    public function testGetFormType(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertEquals(NumberType::class, $this->floatType->getFormType($metadata));
    }



    public function testConfigureFormOptions(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $resolver = new OptionsResolver();
        $this->floatType->configureFormOptions($resolver, $metadata);

        $this->assertEquals([], $resolver->resolve());
    }


}
