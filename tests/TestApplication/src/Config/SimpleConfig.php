<?php

namespace Jbtronics\UserConfigBundle\Tests\TestApplication\Config;

use Jbtronics\UserConfigBundle\Metadata\ConfigClass;
use Jbtronics\UserConfigBundle\Metadata\ConfigEntry;

#[ConfigClass()]
class SimpleConfig
{
    #[ConfigEntry("object")]
    private string $value1 = 'default';

    #[ConfigEntry("integer")]
    private ?int $value2 = null;

    #[ConfigEntry("boolean")]
    private bool $value3 = false;
}