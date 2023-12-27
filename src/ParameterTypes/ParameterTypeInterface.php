<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Schema\SettingsSchema;

/**
 * This is the base interface for all config entry types, defining how to map the value in the config class to a
 * normalized form, which can be serialized and stored in the storage provider.
 */
interface ParameterTypeInterface
{
    /**
     * Convert the given PHP value to a normalized form, which can be serialized and stored in the storage provider.
     * Only values which can be JSON encoded are allowed as return value.
     * @param  mixed  $value
     * @param  string $property The name of the property which is currently handled
     * @param  SettingsSchema  $configSchema The schema of the currently handled config class
     * @return int|string|float|bool|array|null
     */
    public function convertPHPToNormalized(mixed $value, SettingsSchema $configSchema, string $property): int|string|float|bool|array|null;

    /**
     * Convert the given normalized value to a PHP value.
     * @param  int|string|float|bool|array|null  $value
     * @param  string  $property  The name of the property which is currently handled
     * @param  SettingsSchema  $configSchema  The schema of the currently handled config class
     * @return mixed
     */
    public function convertNormalizedToPHP(int|string|float|bool|array|null $value, SettingsSchema $configSchema, string $property): mixed;
}