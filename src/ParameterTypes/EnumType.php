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
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumType implements ParameterTypeInterface, ParameterTypeWithFormDefaultsInterface
{

    public function convertPHPToNormalized(
        mixed $value,
        ParameterMetadata $parameterMetadata,
    ): int|string|float|bool|array|null {
        //Null values get directly returned
        if ($value === null) {
            return null;
        }

        //Extract the class name from the parameter metadata
        /** @phpstan-var class-string<\BackedEnum> $class */
        $class = $parameterMetadata->getOptions()['class'] ?? throw new \LogicException('Missing class option for enum type!');

        //Check if the value is an instance of the class
        if (!is_a($value, $class)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be an instance of enum "%s", but "%s" given.', $parameterMetadata->getName(), $class, gettype($value)));
        }

        //Ensure that the value is a backed enum
        if (!is_a($value, \BackedEnum::class)) {
            throw new \LogicException(sprintf('The enum "%s" must be a backed enum!', $class));
        }

        //Convert the value to a normalized value
        return $value->value;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterMetadata $parameterMetadata,
    ): mixed {

        //Null values get directly returned
        if ($value === null) {
            return null;
        }

        //Extract the class name from the parameter metadata
        /** @phpstan-var class-string<\BackedEnum> $class */
        $class = $parameterMetadata->getOptions()['class'] ?? throw new \LogicException('Missing class option for enum type!');

        //Ensure that the value is a backed enum
        if (!is_a($class, \BackedEnum::class, true)) {
            throw new \LogicException(sprintf('The enum "%s" must be a backed enum!', $class));
        }

        //Convert the value to a backed enum
        return $class::from($value);
    }

    public function getFormType(ParameterMetadata $parameterMetadata): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\EnumType::class;
    }

    public function configureFormOptions(OptionsResolver $resolver, ParameterMetadata $parameterMetadata): void
    {
        $resolver->setDefaults(
            [
                'class' => $parameterMetadata->getOptions()['class'] ?? throw new \LogicException('Missing class option for enum type!'),
            ]
        );
    }
}
