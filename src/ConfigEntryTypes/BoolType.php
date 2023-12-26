<?php

namespace Jbtronics\UserConfigBundle\ConfigEntryTypes;

use Jbtronics\UserConfigBundle\Schema\ConfigSchema;

class BoolType implements ConfigEntryTypeInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        ConfigSchema $configSchema,
        string $property
    ): int|string|float|bool|array|null {
        if (!is_bool($value) && !is_null($value)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be a string, but "%s" given.', $property, gettype($value)));
        }
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ConfigSchema $configSchema,
        string $property
    ): ?int {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}