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

namespace Jbtronics\SettingsBundle\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class represents contains the relevant information about a settings parameter.
 */
class ParameterMetadata
{

    /**
     * @param  class-string  $className  The class name of the settings class, which contains this parameter.
     * @param  string  $propertyName  The name of the property, which is marked as a settings parameter.
     * @param  string  $type  The type of this configuration entry. This must be a class string of a service implementing ConfigEntryTypeInterface
     * @phpstan-param class-string<ParameterTypeInterface> $type
     * @param  bool  $nullable  Whether the value of the property can be null.
     * @param  string|null  $name  The optional name of this configuration entry. If not set, the name of the property is used.
     * @param  string|TranslatableInterface|null  $label  A user friendly label for this configuration entry, which is shown in the UI.
     * @param  string|TranslatableInterface|null  $description  A small descrpiton for this configuration entry, which is shown in the UI.
     * @param  array  $options  An array of extra options, which are passed to the ConfigEntryTypeInterface implementation.
     * @param  string|null  $formType  The form type to use for this configuration entry. If not set, the form type is guessed from the parameter type.
     * @phpstan-param class-string<AbstractType>|null $formType
     * @param  array  $formOptions  An array of extra options, which are passed to the form type. This will override the values from the parameterType
     * @param  string[] $groups The groups, which this parameter should belong to. Groups can be used to only render subsets of the configuration entries in the UI.
     */
    public function __construct(
        private readonly string $className,
        private readonly string $propertyName,
        private readonly string $type,
        private readonly bool $nullable,
        private readonly ?string $name = null,
        private readonly string|TranslatableInterface|null $label = null,
        private readonly string|TranslatableInterface|null $description = null,
        private readonly array $options = [],
        public readonly ?string $formType = null,
        public readonly array $formOptions = [],
        public readonly array $groups = [],
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name ?? $this->propertyName;
    }

    public function getLabel(): TranslatableInterface|string
    {
        return $this->label ?? $this->getName();
    }

    public function getDescription(): TranslatableInterface|string|null
    {
        return $this->description;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * Checks whether the value of this parameter can be null.
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Returns the groups, which this parameter belongs to.
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}