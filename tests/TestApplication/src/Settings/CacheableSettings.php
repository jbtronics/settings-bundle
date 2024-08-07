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
use Jbtronics\SettingsBundle\ParameterTypes\ArrayType;
use Jbtronics\SettingsBundle\ParameterTypes\SerializeType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;

#[Settings(storageAdapter: InMemoryStorageAdapter::class, cacheable: true)]
class CacheableSettings implements ResettableSettingsInterface
{
    #[SettingsParameter(envVar: 'bool:ENV_VALUE1', envVarMode: EnvVarMode::OVERWRITE)]
    public bool $bool = true;

    #[SettingsParameter(envVar: 'ENV_VALUE2', envVarMode: EnvVarMode::OVERWRITE)]
    public ?string $string = null;

    #[SettingsParameter]
    public TestEnum $enum = TestEnum::BAR;

    #[SettingsParameter]
    public \DateTimeImmutable $dateTime;

    #[SettingsParameter(type: ArrayType::class, options: ['type' => StringType::class])]
    public array $array = ['foo' => 'bar', 'bar' => 'foo'];

    #[EmbeddedSettings]
    public SimpleSettings $simpleSettings;

    #[EmbeddedSettings(CircularEmbedSettings::class, groups: ['group1'], label: "Override Label", description: "Override Description", formOptions: ["attr" => ["class" => "test"]])]
    /** @var CircularEmbedSettings */
    public object $circularSettings;

    public function __construct()
    {
        $this->dateTime = new \DateTimeImmutable();
    }

    public function resetToDefaultValues(): void
    {
        $this->dateTime = new \DateTimeImmutable();
    }
}