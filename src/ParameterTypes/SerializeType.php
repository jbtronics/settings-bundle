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


namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;

/**
 * This parameter types uses PHPs serialize() and unserialize() to store complex data like arrays or even objects
 * as settings values. Consider using this type only if you are sure that the data you are storing is safe to be
 * unserialized. In many cases implementing a custom parameter type is the better choice.
 */
class SerializeType implements ParameterTypeInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        ParameterMetadata $parameterMetadata
    ): string {
        return serialize($value);
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterMetadata $parameterMetadata
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be a string, but "%s" given.', $parameterMetadata->getName(), gettype($value)));
        }

        //Allow the user to specify options for the unserialize function
        $options = [
            'allowed_classes' => $parameterMetadata->getOptions()['allowed_classes'] ?? true,
            'max_depth' => $parameterMetadata->getOptions()['max_depth'] ?? 8,
        ];

        return unserialize($value, $options);
    }
}