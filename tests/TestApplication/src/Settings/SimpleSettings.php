<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;

#[Settings(storageAdapter: InMemoryStorageAdapter::class)]
class SimpleSettings
{
    #[SettingsParameter(StringType::class)]
    private string $value1 = 'default';

    #[SettingsParameter(IntType::class, name: 'property2')]
    private ?int $value2 = null;

    #[SettingsParameter(BoolType::class)]
    private bool $value3 = false;

    public function getValue1(): string
    {
        return $this->value1;
    }

    public function setValue1(string $value1): void
    {
        $this->value1 = $value1;
    }

    public function getValue2(): ?int
    {
        return $this->value2;
    }

    public function setValue2(?int $value2): void
    {
        $this->value2 = $value2;
    }

    public function getValue3(): bool
    {
        return $this->value3;
    }

    public function setValue3(bool $value3): void
    {
        $this->value3 = $value3;
    }

}