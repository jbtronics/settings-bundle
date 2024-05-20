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
use Jbtronics\SettingsBundle\ParameterTypes\SerializeType;
use PHPUnit\Framework\TestCase;

class SerializeTypeTest extends TestCase
{

    private SerializeType $type;

    protected function setUp(): void
    {
        $this->type = new SerializeType();
    }

    public function testConvertPHPToNormalized(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);

        //Serializing basic values should be fine
        $this->assertEquals('s:5:"Hello";', $this->type->convertPHPToNormalized('Hello', $metadata));
        $this->assertEquals('i:1;', $this->type->convertPHPToNormalized(1, $metadata));

        //Serializing arrays should be fine
        $this->assertEquals('a:1:{i:0;s:5:"Hello";}', $this->type->convertPHPToNormalized(['Hello'], $metadata));
    }

    public function testConvertNormalizedToPHP(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn([]);


        //Null should be passed through
        $this->assertNull($this->type->convertNormalizedToPHP(null, $metadata));

        //Unserializing basic values should be fine
        $this->assertEquals('Hello', $this->type->convertNormalizedToPHP(serialize("Hello"), $metadata));
        $this->assertEquals(1, $this->type->convertNormalizedToPHP(serialize(1), $metadata));

        //Unserializing arrays should be fine
        $this->assertEquals(['Hello'], $this->type->convertNormalizedToPHP(serialize(['Hello']), $metadata));

        //By default, we should be able to unserialize any object
        $d = new \DateTimeImmutable("now");
        $this->assertEquals($d, $this->type->convertNormalizedToPHP(serialize($d), $metadata));
    }

    public function testConvertNormalizedToPHPWithClassOption(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);
        //Disable unserializing objects
        $metadata->method('getOptions')->willReturn(['allowed_classes' => false]);

        //By default, we should be able to unserialize any object
        $d = new \DateTimeImmutable("now");
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $this->type->convertNormalizedToPHP(serialize($d), $metadata));
    }

    public function testConvertNormalizedToPHPInvalidType(): void
    {
        $metadata = $this->createMock(ParameterMetadata::class);
        $metadata->method('getOptions')->willReturn([]);

        $this->expectException(\LogicException::class);
        $this->type->convertNormalizedToPHP(1, $metadata);
    }
}
