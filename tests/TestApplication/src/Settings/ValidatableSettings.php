<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings;

use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

#[Settings(storageAdapter: InMemoryStorageAdapter::class)]
class ValidatableSettings
{
    #[SettingsParameter(StringType::class)]
    #[NotBlank(message: 'Value must not be blank')]
    public string $value1 = 'default';

    #[SettingsParameter(IntType::class, name: 'property2')]
    #[Positive(message: 'Value must be greater than 0')]
    public ?int $value2 = 5;
}