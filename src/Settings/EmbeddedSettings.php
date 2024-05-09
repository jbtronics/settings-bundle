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


namespace Jbtronics\SettingsBundle\Settings;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This attribute marks that a settings class should be embedded into another settings class as property.
 * This attribute is applied to the property into which the settings class should be embedded.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class EmbeddedSettings
{
    /**
     * @param  string|null  $target The class name of the settings class, which should be embedded into this property. If null, this is automatically discovered from the property type.
     * @phpstan-param class-string $target
     * @param  string[]|null  $groups  The groups, which this parameter should belong to. Groups can be used to only render subsets of the configuration entries in the UI. If not set, the parameter is assigned to the default group set in the settings class.
     * @param string|TranslatableInterface|null $label A user-friendly label for this configuration entry, which is shown in the UI. Overrides the label value of the embedded settings class.
     * @param string|TranslatableInterface|null $description A small description for this configuration entry, which is shown in the UI. Overrides the description value of the embedded settings class.
     * @param array|null $formOptions The form options, which are passed to the form type of the embedded settings class.
     */
    public function __construct(
        public readonly ?string $target = null,
        public readonly ?array $groups = null,
        public readonly string|TranslatableInterface|null $label = null,
        public readonly string|TranslatableInterface|null $description = null,
        public readonly array|null $formOptions = null,
    ) {
    }

}