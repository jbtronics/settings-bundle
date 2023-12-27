<?php

namespace Jbtronics\SettingsBundle\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This attribute marks a property inside a settings class as a settings entry of this class.
 * The value is mapped by the configured type and stored in the storage provider.
 * The attributes define various metadata for the configuration entry like a user friendly label, description and
 * other properties.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SettingsParameter
{
    /**
     * @param  string  $type The type of this configuration entry. This must be a class string of a service implementing ConfigEntryTypeInterface
     * @phpstan-param class-string<ParameterTypeInterface> $type
     * @param  string|null  $name The optional name of this configuration entry. If not set, the name of the property is used.
     * @param  string|TranslatableInterface|null  $label A user friendly label for this configuration entry, which is shown in the UI.
     * @param  string|TranslatableInterface|null  $description A small descrpiton for this configuration entry, which is shown in the UI.
     * @param  array  $extra_options An array of extra options, which are passed to the ConfigEntryTypeInterface implementation.
     */
    public function __construct(
        private readonly string $type,
        private readonly ?string $name = null,
        private readonly string|TranslatableInterface|null $label = null,
        private readonly string|TranslatableInterface|null $description = null,
        private readonly array $extra_options = [],
    )
    {

    }
}