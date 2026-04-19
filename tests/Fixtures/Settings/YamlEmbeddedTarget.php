<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Fixtures\Settings;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;

/**
 * A settings class used as an embedded target in YAML configuration tests.
 * This one uses attributes since it needs to be a valid settings class itself.
 */
#[Settings(storageAdapter: InMemoryStorageAdapter::class)]
class YamlEmbeddedTarget
{
    #[SettingsParameter]
    public string $value = 'embedded_default';
}
