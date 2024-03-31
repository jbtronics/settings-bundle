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

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\EnvVarValueResolver;
use Jbtronics\SettingsBundle\Manager\EnvVarValueResolverInterface;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

class EnvVarValueResolverTest extends KernelTestCase
{

    private EnvVarValueResolver $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(EnvVarValueResolverInterface::class);
    }

    public function testGetValue(): void
    {
        $_ENV['TEST_ENV'] = "true";

        //Directly accessing the value from the environment variable
        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'TEST_ENV');
        $this->assertTrue($this->service->hasValue($paramMetadata));
        $this->assertSame('true', $this->service->getValue($paramMetadata));

        //Use a the environment variable processors of symfony
        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'bool:TEST_ENV');
        $this->assertTrue($this->service->hasValue($paramMetadata));
        $this->assertTrue($this->service->getValue($paramMetadata));

        //Nested variable filters
        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'not:bool:TEST_ENV');
        $this->assertTrue($this->service->hasValue($paramMetadata));
        $this->assertFalse($this->service->getValue($paramMetadata));
    }

    public function testGetValueMappingClosure(): void
    {
        $_ENV['TEST_ENV'] = "true";

        $closure = function ($value) {
            //Assert that the value is the same as the one from the environment variable
            $this->assertSame('true', $value);

            return 120.23;
        };

        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'TEST_ENV', envVarMapper: $closure);

        $this->assertTrue($this->service->hasValue($paramMetadata));
        $this->assertSame(120.23, $this->service->getValue($paramMetadata));
    }

    public function testGetValueMappingParamType(): void
    {
        $_ENV['TEST_ENV'] = "true";

        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'TEST_ENV', envVarMapper: BoolType::class);

        $this->assertTrue($this->service->hasValue($paramMetadata));
        $this->assertTrue($this->service->getValue($paramMetadata));
    }

    public function testGetValueNoEnvVarDefinedOnMetadata(): void
    {
        $this->expectException(\LogicException::class);

        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false);
        $this->service->getValue($paramMetadata);
    }

    public function testHasValueNoEnvVarDefinedOnMetadata(): void
    {
        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false);
        $this->assertFalse($this->service->hasValue($paramMetadata));
    }

    public function testGetValueEnvNotDefined(): void
    {
        $this->expectException(EnvNotFoundException::class);

        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'NOT_DEFINED');
        $this->service->getValue($paramMetadata);
    }

    public function testHasValueEnvNotDefined(): void
    {
        $paramMetadata = new ParameterMetadata(SimpleSettings::class, 'bar', StringType::class, false,
            envVar: 'NOT_DEFINED');
        $this->assertFalse($this->service->hasValue($paramMetadata));
    }
}
