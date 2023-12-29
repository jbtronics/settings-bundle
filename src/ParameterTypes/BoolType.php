<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Schema\SettingsSchema;

class BoolType implements ParameterTypeInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        SettingsSchema $configSchema,
        string $parameterName
    ): int|string|float|bool|array|null {
        if (!is_bool($value) && !is_null($value)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be a string, but "%s" given.', $parameterName, gettype($value)));
        }

        return $value;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        SettingsSchema $configSchema,
        string $parameterName
    ): ?int {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}