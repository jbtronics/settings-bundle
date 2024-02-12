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

use Jbtronics\SettingsBundle\ParameterTypes\DatetimeType;
use PHPUnit\Framework\TestCase;

class DatetimeTypeTest extends TestCase
{

    private DatetimeType $datetimeType;

    public function setUp(): void
    {
        $this->datetimeType = new DatetimeType();
    }

    public function testConvertPHPToNormalizedDateTime(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTime::class]);

        //Null should be returned as null
        $this->assertNull($this->datetimeType->convertPHPToNormalized(null, $metadata));

        //Test with a DateTime object
        $this->assertEquals('2024-01-01T00:00:00+00:00', $this->datetimeType->convertPHPToNormalized(new \DateTime('2024-01-01', new \DateTimeZone('UTC')), $metadata));
        $this->assertEquals('2024-01-01T00:00:00+01:00', $this->datetimeType->convertPHPToNormalized(new \DateTime('2024-01-01', new \DateTimeZone('Europe/Berlin')), $metadata));
    }

    public function testConvertPHPToNormalizedDateTimeImmutable(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTimeImmutable::class]);

        //Null should be returned as null
        $this->assertNull($this->datetimeType->convertPHPToNormalized(null, $metadata));

        //Test with a DateTimeImmutable object
        $metadata->method('getOptions')->willReturn(['class' => \DateTimeImmutable::class]);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $this->datetimeType->convertPHPToNormalized(new \DateTimeImmutable('2024-01-01', new \DateTimeZone('UTC')), $metadata));
        $this->assertEquals('2024-01-01T00:00:00+01:00', $this->datetimeType->convertPHPToNormalized(new \DateTimeImmutable('2024-01-01', new \DateTimeZone('Europe/Berlin')), $metadata));
    }

    public function testConvertPHPToNormalizedInvalidClass(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTime::class]);

        $this->expectException(\LogicException::class);
        $this->datetimeType->convertPHPToNormalized(new \stdClass(), $metadata);
    }

    public function testConvertNormalizedToPHPDatetime(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTime::class]);

        //Null should be returned as null
        $this->assertNull($this->datetimeType->convertNormalizedToPHP(null, $metadata));

        //Test with a DateTime object
        $this->assertEquals(new \DateTime('2024-01-01', new \DateTimeZone('UTC')), $this->datetimeType->convertNormalizedToPHP('2024-01-01T00:00:00+00:00', $metadata));
        $this->assertEquals(new \DateTime('2024-01-01', new \DateTimeZone('Europe/Berlin')), $this->datetimeType->convertNormalizedToPHP('2024-01-01T00:00:00+01:00', $metadata));
    }

    public function testConvertNormalizedToPHPDatetimeImmutable(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTimeImmutable::class]);

        //Null should be returned as null
        $this->assertNull($this->datetimeType->convertNormalizedToPHP(null, $metadata));

        //Test with a DateTimeImmutable object
        $this->assertEquals(new \DateTimeImmutable('2024-01-01', new \DateTimeZone('UTC')), $this->datetimeType->convertNormalizedToPHP('2024-01-01T00:00:00+00:00', $metadata));
        $this->assertEquals(new \DateTimeImmutable('2024-01-01', new \DateTimeZone('Europe/Berlin')), $this->datetimeType->convertNormalizedToPHP('2024-01-01T00:00:00+01:00', $metadata));
    }

    public function testConfigureFormOptions(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn(['class' => \DateTimeImmutable::class]);
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $this->datetimeType->configureFormOptions($resolver, $metadata);

        $this->assertEquals(['input' => 'datetime_immutable'], $resolver->resolve());
    }

    public function testGetFormType(): void
    {
        $metadata = $this->createMock(\Jbtronics\SettingsBundle\Metadata\ParameterMetadata::class);

        $this->assertEquals(\Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, $this->datetimeType->getFormType($metadata));
    }
}
