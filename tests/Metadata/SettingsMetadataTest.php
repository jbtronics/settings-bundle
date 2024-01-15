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

namespace Jbtronics\SettingsBundle\Tests\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use PhpParser\Node\Param;
use PHPUnit\Framework\TestCase;

class SettingsMetadataTest extends TestCase
{
    private SettingsMetadata $configSchema;
    private Settings $configClass;
    private array $parameterMetadata = [];

    public function setUp(): void
    {
        $this->parameterMetadata = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, name: 'name2'),
            new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: true, name: 'name3',label:  'label3', description: 'description3'),
        ];

        $this->configSchema = new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $this->parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
        );
    }

    public function testGetClassName(): void
    {
        $this->assertEquals(self::class, $this->configSchema->getClassName());
    }

    public function testGetParameters(): void
    {
        $this->assertEquals([
            'property1' => $this->parameterMetadata[0],
            'name2' => $this->parameterMetadata[1],
            'name3' => $this->parameterMetadata[2],
        ], $this->configSchema->getParameters());
    }

    public function testHasParameter(): void
    {
        $this->assertTrue($this->configSchema->hasParameter('property1'));
        $this->assertTrue($this->configSchema->hasParameter('name2'));
        $this->assertTrue($this->configSchema->hasParameter('name3'));
        $this->assertFalse($this->configSchema->hasParameter('property4'));
    }

    public function testGetParameter(): void
    {
        $this->assertEquals($this->parameterMetadata[0], $this->configSchema->getParameter('property1'));
        $this->assertEquals($this->parameterMetadata[1], $this->configSchema->getParameter('name2'));
        $this->assertEquals($this->parameterMetadata[2], $this->configSchema->getParameter('name3'));
    }

    public function testGetParameterInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->configSchema->getParameter('property4');
    }

    public function testGetParameterByPropertyName(): void
    {
        $this->assertEquals($this->parameterMetadata[0], $this->configSchema->getParameterByPropertyName('property1'));
        $this->assertEquals($this->parameterMetadata[1], $this->configSchema->getParameterByPropertyName('property2'));
        $this->assertEquals($this->parameterMetadata[2], $this->configSchema->getParameterByPropertyName('property3'));
    }

    public function testGetParameterByPropertyNameInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->configSchema->getParameterByPropertyName('property4');
    }

    public function testHasParameterWithPropertyName(): void
    {
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property1'));
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property2'));
        $this->assertTrue($this->configSchema->hasParameterWithPropertyName('property3'));
        $this->assertFalse($this->configSchema->hasParameterWithPropertyName('property4'));
    }

    public function testGetPropertyNames(): void
    {
        $this->assertEquals([
            'property1',
            'property2',
            'property3',
        ], $this->configSchema->getPropertyNames());
    }
}
