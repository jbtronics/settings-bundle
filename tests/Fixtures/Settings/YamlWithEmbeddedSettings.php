<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Fixtures\Settings;

/**
 * A plain PHP settings class with an embedded settings property, configured via YAML.
 */
class YamlWithEmbeddedSettings
{
    private string $title = 'default_title';

    private ?YamlEmbeddedTarget $child = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getChild(): ?YamlEmbeddedTarget
    {
        return $this->child;
    }

    public function setChild(?YamlEmbeddedTarget $child): void
    {
        $this->child = $child;
    }
}
