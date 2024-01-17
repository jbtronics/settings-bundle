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

namespace Jbtronics\SettingsBundle\Settings;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This attribute marks a property inside a settings class as a settings entry of this class.
 * The value is mapped by the configured type and stored in the storage provider.
 * The attributes define various metadata for the configuration entry like a user friendly label, description and
 * other properties.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SettingsParameter
{
    /**
     * @param  string|null  $type  The type of this configuration entry. This must be a class string of a service implementing ParameterTypeInterface. If it is not set, the type is guessed from the property type.
     * @phpstan-param class-string<ParameterTypeInterface> $type
     * @param  string|null  $name  The optional name of this configuration entry. If not set, the name of the property is used.
     * @param  string|TranslatableInterface|null  $label  A user friendly label for this configuration entry, which is shown in the UI.
     * @param  string|TranslatableInterface|null  $description  A small description for this configuration entry, which is shown in the UI.
     * @param  array  $options  An array of extra options, which can detail configure the behavior of the ParameterType.
     * @param  string|null  $formType  The form type to use for this configuration entry. If not set, the form type is guessed from the parameter type.
     * @phpstan-param class-string<AbstractType>|null $formType
     * @param  array  $formOptions  An array of extra options, which are passed to the form type.
     * @param  bool|null  $nullable  Whether the value of the property can be null. If not set, the value is guessed from the property type.
     * @param  string[]|null  $groups  The groups, which this parameter should belong to. Groups can be used to only render subsets of the configuration entries in the UI. If not set, the parameter is assigned to the default group set in the settings class.
     */
    public function __construct(
        public readonly ?string $type = null,
        public readonly ?string $name = null,
        public readonly string|TranslatableInterface|null $label = null,
        public readonly string|TranslatableInterface|null $description = null,
        public readonly array $options = [],
        public readonly ?string $formType = null,
        public readonly array $formOptions = [],
        public readonly ?bool $nullable = null,
        public readonly ?array $groups = null,
    ) {
    }


}