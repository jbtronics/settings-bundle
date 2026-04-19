<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Fixtures\Settings;

/**
 * A plain PHP settings class with no attributes.
 * All metadata is provided via YAML configuration.
 */
class YamlConfiguredSettings
{
    private string $name = 'default_name';

    private ?int $count = null;

    private bool $enabled = true;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
