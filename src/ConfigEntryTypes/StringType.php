<?php

namespace Jbtronics\UserConfigBundle\ConfigEntryTypes;

use Jbtronics\UserConfigBundle\Metadata\ConfigEntry;
use Jbtronics\UserConfigBundle\Schema\ConfigSchema;

class StringType implements ConfigEntryTypeInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        ConfigSchema $configSchema,
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
        ConfigSchema $configSchema,
        string $property
    ): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}