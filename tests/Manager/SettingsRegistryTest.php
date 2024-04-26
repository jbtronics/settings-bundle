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

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\NonCloneableClass;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\GuessableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\MergeableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\NonCloneableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\ValidatableSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\VersionedSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CircularEmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;

class SettingsRegistryTest extends TestCase
{
    public function testGetConfigClasses(): void
    {
        $configurationRegistry = new SettingsRegistry(
            [
                __DIR__.'/../TestApplication/src/Settings/',
            ],
            new NullAdapter(),
            false,
        );

        $this->assertEquals([
            'simple' => SimpleSettings::class,
            'test234' => ValidatableSettings::class,
            'guessable' => GuessableSettings::class,
            'versioned' => VersionedSettings::class,
            'circularembed' => CircularEmbedSettings::class,
            'embed' => EmbedSettings::class,
            'envvar' => EnvVarSettings::class,
            'mergeable' => MergeableSettings::class,
            'noncloneable' => NonCloneableSettings::class
        ], $configurationRegistry->getSettingsClasses());
    }

    public function testGetSettingsClassByName(): void
    {
        $configurationRegistry = new SettingsRegistry(
            [
                __DIR__.'/../TestApplication/src/Settings/',
            ],
            new NullAdapter(),
            false,
        );

        $this->assertEquals(SimpleSettings::class, $configurationRegistry->getSettingsClassByName('simple'));
        $this->assertEquals(ValidatableSettings::class, $configurationRegistry->getSettingsClassByName('test234'));
    }
}
