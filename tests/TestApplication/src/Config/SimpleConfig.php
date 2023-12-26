<?php

namespace Jbtronics\UserConfigBundle\Tests\TestApplication\Config;

use Jbtronics\UserConfigBundle\ConfigEntryTypes\BoolType;
use Jbtronics\UserConfigBundle\ConfigEntryTypes\IntType;
use Jbtronics\UserConfigBundle\ConfigEntryTypes\StringType;
use Jbtronics\UserConfigBundle\Metadata\ConfigClass;
use Jbtronics\UserConfigBundle\Metadata\ConfigEntry;

#[ConfigClass()]
class SimpleConfig
{
    #[ConfigEntry(StringType::class)]
    private string $value1 = 'default';

    #[ConfigEntry(IntType::class)]
    private ?int $value2 = null;

    #[ConfigEntry(BoolType::class)]
    private bool $value3 = false;
}