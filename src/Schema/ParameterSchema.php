<?php

namespace Jbtronics\SettingsBundle\Schema;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class represents the schema of a settings parameter.
 */
class ParameterSchema
{
    /**
     * @param class-string  $className The class name of the settings class, which contains this parameter.
     * @param string  $propertyName The name of the property, which is marked as a settings parameter.
     * @param  string  $type The type of this configuration entry. This must be a class string of a service implementing ConfigEntryTypeInterface
     * @phpstan-param class-string<ParameterTypeInterface> $type
     * @param  string|null  $name The optional name of this configuration entry. If not set, the name of the property is used.
     * @param  string|TranslatableInterface|null  $label A user friendly label for this configuration entry, which is shown in the UI.
     * @param  string|TranslatableInterface|null  $description A small descrpiton for this configuration entry, which is shown in the UI.
     * @param  array  $extra_options An array of extra options, which are passed to the ConfigEntryTypeInterface implementation.
     */
    public function __construct(
        private readonly string $className,
        private readonly string $propertyName,
        private readonly string $type,
        private readonly ?string $name = null,
        private readonly string|TranslatableInterface|null $label = null,
        private readonly string|TranslatableInterface|null $description = null,
        private readonly array $extra_options = [],
    )
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name ?? $this->propertyName;
    }

    public function getLabel(): TranslatableInterface|string
    {
        return $this->label ?? $this->getName();
    }

    public function getDescription(): TranslatableInterface|string|null
    {
        return $this->description;
    }

    public function getExtraOptions(): array
    {
        return $this->extra_options;
    }
}