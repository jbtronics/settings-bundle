<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;

class StringType implements ParameterTypeInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        SettingsSchema $configSchema,
        string $property
    ): int|string|float|bool|array|null
    {
        if (!is_string($value) && !is_null($value)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be a string, but "%s" given.', $property, gettype($value)));
        }

        return $value;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        SettingsSchema $configSchema,
        string $property
    ): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}