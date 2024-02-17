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
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StringTypeTest extends TestCase
{

    private StringType $stringType;

    public function setUp(): void
    {
        $this->stringType = new StringType();
    }

    public function testConvertNormalizedToPHP(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertEquals('foo', $this->stringType->convertNormalizedToPHP('foo', $metadata));
        $this->assertNull($this->stringType->convertNormalizedToPHP(null, $metadata));
    }

    public function testConvertPHPToNormalized(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertEquals('foo', $this->stringType->convertPHPToNormalized('foo', $metadata));
        $this->assertNull($this->stringType->convertPHPToNormalized(null, $metadata));
    }

    public function testConvertPHPToNormalizedInvalidType(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->expectException(LogicException::class);
        $this->stringType->convertPHPToNormalized(1, $metadata);
    }


    public function testGetFormType(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $this->assertEquals(TextType::class, $this->stringType->getFormType($metadata));
    }

    public function testConfigureFormOptions(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        $resolver  = new OptionsResolver();
        $this->stringType->configureFormOptions($resolver, $metadata);

        $this->assertSame([], $resolver->resolve());
    }
}
