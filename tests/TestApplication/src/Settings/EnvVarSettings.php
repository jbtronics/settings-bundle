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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings;

use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

#[Settings(storageAdapter: InMemoryStorageAdapter::class)]
class EnvVarSettings
{
    #[SettingsParameter(envVar: 'ENV_VALUE1')]
    public string $value1 = 'default';

    #[SettingsParameter(envVar: 'bool:ENV_VALUE2', envVarMode: EnvVarMode::OVERWRITE)]
    public bool $value2 = false;

    #[SettingsParameter(envVar: 'ENV_VALUE3', envVarMode: EnvVarMode::OVERWRITE, envVarMapper: [self::class, 'envVarMapper'])]
    public float $value3 = 0.0;

    #[SettingsParameter(envVar: 'ENV_VALUE4', envVarMode: EnvVarMode::OVERWRITE_PERSIST, envVarMapper: BoolType::class)]
    public ?bool $value4 = false;

    #[SettingsParameter(envVar: 'ENV_VALUE5', envVarMode: EnvVarMode::OVERWRITE_PERSIST)]
    #[PositiveOrZero]
    public float $value5 = 0.0;


    public static function envVarMapper(mixed $value): mixed
    {
        return 123.4;
    }

}