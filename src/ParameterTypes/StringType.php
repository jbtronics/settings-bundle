<?php


/*
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

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StringType implements ParameterTypeInterface, ParameterTypeWithFormDefaultsInterface
{
    public function convertPHPToNormalized(
        mixed $value,
        ParameterMetadata $parameterSchema,
    ): int|string|float|bool|array|null
    {
        if (!is_string($value) && !is_null($value)) {
            throw new \LogicException(sprintf('The value of the property "%s" must be a string, but "%s" given.', $parameterSchema->getName(), gettype($value)));
        }

        return $value;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterMetadata $parameterSchema,
    ): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public function getFormType(ParameterMetadata $parameterSchema): string
    {
        return TextType::class;
    }

    public function configureFormOptions(OptionsResolver $resolver, ParameterMetadata $parameterSchema): void
    {
        //No options required
    }
}