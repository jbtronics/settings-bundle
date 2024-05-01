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
use Symfony\Component\Form\AbstractType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class represents contains the relevant information about a settings parameter.
 */
class ParameterMetadata
{

    private readonly \Closure|string|null $envVarMapper;

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
     * @param  string|null  $envVar  The name of the environment variable, which should be used to fill this parameter. If not set, the parameter is not filled by an environment variable.
     * @param  EnvVarMode  $envVarMode  The mode in which the environment variable should be used to fill the parameter. Defaults to EnvVarMode::INITIAL
     * @param  callable|string|null  $envVarMapper  A mapper, which is used to map the value from the environment variable to the parameter value. It can be either a ParameterTypeInterface service, or a callable, which takes the value from the environment variable as argument and returns the mapped value.
     * @phpstan-param callable(mixed): mixed|class-string<ParameterTypeInterface>|null $envVarMapper
     * @param  bool $cloneable Whether this property should be cloned (a new instance is created), when the settings class is cloned. If false, the property instance is shared between the original and the cloned settings class. This does only affect object properties.
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
        private readonly ?string $formType = null,
        private readonly array $formOptions = [],
        private readonly array $groups = [],
        private readonly ?string $envVar = null,
        private readonly EnvVarMode $envVarMode = EnvVarMode::INITIAL,
        callable|string|null $envVarMapper = null,
        private readonly bool $cloneable = true,
    ) {
        if (is_callable($envVarMapper)) {
            $envVarMapper = $envVarMapper(...);
        }

        $this->envVarMapper = $envVarMapper;
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

    /**
     * Returns the name of the environment variable, which should be used to fill this parameter.
     * Null if no environment variable should be used to fill this parameter.
     * @return string|null
     */
    public function getEnvVar(): ?string
    {
        return $this->envVar;
    }

    /**
     * If the environment variable is set, this method returns the base env var, which is the last part of the expression.
     * So if the env var expression is bool:FOO_BAR, the base env var is FOO_BAR.
     * If no environment variable is set, this method returns null.
     * @return string|null
     */
    public function getBaseEnvVar(): ?string
    {
        if ($this->envVar === null){
            return null;
        }

        //If the expressions dont contain any colons, the base env var is the same as the expression
        if (!str_contains($this->envVar, ':')) {
            return $this->envVar;
        }

        //We assume that all env var expressions are using the prefix syntax, and therefore the base env var is the last part
        return substr($this->envVar, strrpos($this->envVar, ':') + 1);
    }

    /**
     * Returns the mode in which the environment variable should be used to fill the parameter.
     * @return EnvVarMode
     */
    public function getEnvVarMode(): EnvVarMode
    {
        return $this->envVarMode;
    }

    /**
     * Returns the mapper, which is used to map the value from the environment variable to the parameter value.
     * This can be a closure or the name of the ParameterType service, which should be used to map the value.
     * Null if no mapping function is set.
     * @return \Closure|string|null
     * @phpstan-return \Closure(mixed): mixed|class-string<ParameterTypeInterface>|null
     */
    public function getEnvVarMapper(): \Closure|string|null
    {
        return $this->envVarMapper;
    }

    /**
     * Whether this property should be cloned (a new instance is created), when the settings class is cloned.
     * If false, the property instance is shared between the original and the cloned settings class.
     * This does only affect object properties.
     * @return bool
     */
    public function isCloneable(): bool
    {
        return $this->cloneable;
    }

    public function __serialize(): array
    {
        $envVarMapper = $this->envVarMapper;

        //We can not serialize closures, so we convert them to a string which we can use to recreate the closure
        if ($envVarMapper instanceof \Closure) {
            $envVarMapper = "Closure function";
        }

        return [
            'className' => $this->className,
            'propertyName' => $this->propertyName,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'options' => $this->options,
            'formType' => $this->formType,
            'formOptions' => $this->formOptions,
            'groups' => $this->groups,
            'envVar' => $this->envVar,
            'envVarMode' => $this->envVarMode,
            'envVarMapper' => $envVarMapper,
            'cloneable' => $this->cloneable,
        ];
    }
}