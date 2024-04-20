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

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Metadata;

use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;

class ParameterMetadataTest extends TestCase
{

    public function getBaseEnvVarDataProvider(): iterable
    {
        yield [null, null];
        yield ['ENV_VAR1', 'bool:ENV_VAR1'];
        yield ['ENV_VAR1', 'not:bool:ENV_VAR1'];
        yield ['ENV_VAR1', 'ENV_VAR1'];
        yield ['BAR', 'key:FOO:BAR'];
        yield ['BAR', 'default:fallback_param:BAR'];
        yield ['BAR', 'enum:FooEnum:BAR'];
    }

    /**
     * @dataProvider getBaseEnvVarDataProvider
     * @param  string  $expected
     * @param  string  $input
     * @return void
     */
    public function testGetBaseEnvVar(?string $expected, ?string $input): void
    {
        $metadata = new ParameterMetadata(SimpleSettings::class, 'property1', StringType::class, true, envVar: $input);

        $this->assertSame($expected, $metadata->getBaseEnvVar());
    }
}
