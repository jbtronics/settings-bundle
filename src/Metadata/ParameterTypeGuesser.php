<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\DatetimeType;
use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\ParameterTypes\FloatType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;

final class ParameterTypeGuesser implements ParameterTypeGuesserInterface
{
    public function guessParameterType(\ReflectionProperty $property): ?string
    {
        if ($property->hasType()) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();

                // Check for scalar types
                if ($typeName === 'bool') {
                    return BoolType::class;
                }
                if ($typeName === 'int') {
                    return IntType::class;
                }
                if ($typeName === 'string') {
                    return StringType::class;
                }
                if ($typeName === 'float') {
                    return FloatType::class;
                }

                if ($this->isSupportedEnum($typeName)) {
                    return EnumType::class;
                }

                if ($this->isSupportedDateTime($typeName)) {
                    return DatetimeType::class;
                }
            }
        }

        //If we can not guess the type, return null
        return null;
    }

    public function guessOptions(\ReflectionProperty $property): ?array
    {
        if ($property->hasType()) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();

                //Check if type is an enum class, then pass the class name to the metadata
                if ($this->isSupportedEnum($typeName)) {
                    return [
                        'class' => $type->getName(),
                    ];
                }

                if ($this->isSupportedDateTime($typeName)) {
                    return [
                        'class' => $type->getName(),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Checks if the given type is an enum, which can be handled by the EnumType (only backed enums are supported)
     * @param  string  $typeName
     * @return bool
     */
    private function isSupportedEnum(string $typeName): bool
    {
        return is_a($typeName, \BackedEnum::class, true);
    }

    /**
     * Checks if the given type is a datetime class, which can be handled by the DatetimeType. The type must be a
     * concrete datetime class (DateTime or DateTimeImmutable or a subclass), as we can not create instances of the
     * DateTimeInterface
     * @param  string  $typeName
     * @return bool
     */
    private function isSupportedDateTime(string $typeName): bool
    {
        return is_a($typeName, \DateTime::class, true) || is_a($typeName, \DateTimeImmutable::class, true);
    }
}
