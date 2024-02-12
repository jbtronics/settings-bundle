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

/**
 * This parameter type adds support for the \DateTime and \DateTimeImmutable classes and their subclasses.
 * The value is stored in the ATOM format, which is the default format of the \DateTime and \DateTimeImmutable classes.
 */
class DatetimeType implements ParameterTypeInterface, ParameterTypeWithFormDefaultsInterface
{

    public function convertPHPToNormalized(
        mixed $value,
        ParameterMetadata $parameterMetadata
    ): string|null {
        //Null values get directly returned
        if ($value === null) {
            return null;
        }

        //Extract the class name from the parameter metadata
        /** @phpstan-var class-string<\DateTimeInterface> $class */
        $class = $parameterMetadata->getOptions()['class'] ?? throw new \LogicException(sprintf('Missing class option for datetime parameter type on parameter %s!', $parameterMetadata->getName()));

        if (!is_a($class, \DateTimeInterface::class, true)) {
            throw new \LogicException(sprintf('The class defined on parameter %s must implement DateTimeInterface!', $parameterMetadata->getName()));
        }

        //Check if the value is an instance of the class
        if (!is_a($value, $class)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be an instance of "%s", but "%s" given.', $parameterMetadata->getName(), $class, get_debug_type($value)));
        }

        //Convert the value to a normalized value (ATOM format), which can easily be stored in the storage provider
        /** @var \DateTimeInterface $value */
        return $value->format(\DateTimeInterface::ATOM);
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterMetadata $parameterMetadata
    ): \DateTimeInterface|null {
        //Null values get directly returned
        if ($value === null) {
            return null;
        }

        //Extract the class name from the parameter metadata
        /** @phpstan-var class-string<\DateTimeInterface> $class */
        $class = $parameterMetadata->getOptions()['class'] ?? throw new \LogicException(sprintf('Missing class option for datetime parameter type on parameter %s!', $parameterMetadata->getName()));

        if (!is_a($class, \DateTime::class, true) && !is_a($class, \DateTimeImmutable::class, true)) {
            throw new \LogicException(sprintf('The class defined on parameter %s must extend either \DateTime or \DateTimeImmutable!', $parameterMetadata->getName()));
        }

        //Create a new instance of the class with the given value
        $tmp = $class::createFromFormat(\DateTimeInterface::ATOM, $value);

        if ($tmp === false) {
            throw new \RuntimeException(sprintf('Failed to create a new instance of %s!', $class));
        }

        return $tmp;
    }

    public function getFormType(ParameterMetadata $parameterMetadata): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class;
    }

    public function configureFormOptions(OptionsResolver $resolver, ParameterMetadata $parameterMetadata): void
    {
        //Depending on the class, we need to set different input options
        $class = $parameterMetadata->getOptions()['class'] ?? throw new \LogicException(sprintf('Missing class option for datetime parameter type on parameter %s!', $parameterMetadata->getName()));

        if (is_a($class, \DateTime::class, true)) {
            $resolver->setDefaults([
                'input' => 'datetime',
            ]);
        } elseif (is_a($class, \DateTimeImmutable::class, true)) {
            $resolver->setDefaults([
                'input' => 'datetime_immutable',
            ]);
        } else {
            throw new \LogicException(sprintf('The class defined on parameter %s must extend either \DateTime or \DateTimeImmutable!', $parameterMetadata->getName()));
        }
    }
}