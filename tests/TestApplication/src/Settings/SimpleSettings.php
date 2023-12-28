<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

#[Settings()]
class SimpleSettings
{
    #[SettingsParameter(StringType::class)]
    private string $value1 = 'default';

    #[SettingsParameter(IntType::class)]
    private ?int $value2 = null;

    #[SettingsParameter(BoolType::class)]
    private bool $value3 = false;
}