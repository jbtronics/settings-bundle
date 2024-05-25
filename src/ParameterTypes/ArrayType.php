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
 * This parameter type implements the conversion of array values, where each element is converted by a sub-parameter type.
 * The sub-parameter type must be specified in the 'type' option.
 */
final class ArrayType implements ParameterTypeInterface
{
    public function __construct(private readonly ParameterTypeRegistryInterface $parameterTypeRegistry)
    {

    }

    public function convertPHPToNormalized(
        mixed $value,
        ParameterMetadata $parameterMetadata
    ): int|string|float|bool|array|null {
        if (!is_array($value) && !is_null($value)) {
            throw new \InvalidArgumentException(sprintf('The value of the property "%s" must be an array, but "%s" given.', $parameterMetadata->getName(), gettype($value)));
        }

        //For null pass through
        if ($value === null) {
            return null;
        }

        //Iterate over each array element and convert it to a normalized value
        $normalized = [];

        foreach ($value as $key => $element) {
            $subParameterType = $this->getSubParameterType($parameterMetadata);
            $subParameterMetadata = $this->createSubParameterMetadata($parameterMetadata, $key);
            $normalized[$key] = $subParameterType->convertPHPToNormalized($element, $subParameterMetadata);
        }

        return $normalized;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterMetadata $parameterMetadata
    ): mixed {
        if (!is_array($value) && !is_null($value)) {
            throw new \InvalidArgumentException(sprintf('The value of the property "%s" must be an array, but "%s" given.', $parameterMetadata->getName(), gettype($value)));
        }

        //For null pass through
        if ($value === null) {
            //If not nullable, return an empty array
            if (!$parameterMetadata->isNullable()) {
                return [];
            }
            return null;
        }

        //Iterate over each array element and convert it to a normalized value
        $php = [];

        foreach ($value as $key => $element) {
            $subParameterType = $this->getSubParameterType($parameterMetadata);
            $subParameterMetadata = $this->createSubParameterMetadata($parameterMetadata, $key);
            $php[$key] = $subParameterType->convertNormalizedToPHP($element, $subParameterMetadata);
        }

        return $php;
    }

    private function getSubParameterType(ParameterMetadata $parameterMetadata): ParameterTypeInterface
    {
        $class = $parameterMetadata->getOptions()['type']
            ?? throw new \LogicException("You have to specify the ParameterType to use for the array elements in the 'type' option.");

        return $this->parameterTypeRegistry->getParameterType($class);
    }

    private function createSubParameterMetadata(ParameterMetadata $parameterMetadata, int|string $arrayKey): ParameterMetadata
    {
        return new ParameterMetadata(
            className: $parameterMetadata->getClassName(),
            propertyName: $parameterMetadata->getPropertyName() . '[' . $arrayKey . ']',
            type: $parameterMetadata->getOptions()['type'],
            nullable:$parameterMetadata->getOptions()['nullable'] ?? true,
            options: $parameterMetadata->getOptions()['options'] ?? [],
        );
    }
}