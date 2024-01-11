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


namespace Jbtronics\SettingsBundle\Form;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeWithFormDefaultsInterface;
use Jbtronics\SettingsBundle\Schema\ParameterSchema;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsFormBuilder implements SettingsFormBuilderInterface
{
    public function __construct(private readonly ParameterTypeRegistryInterface $parameterTypeRegistry)
    {
    }

    public function buildSettingsForm(FormBuilderInterface $builder, SettingsSchema $settingsSchema, ?array $options = null): void
    {
        foreach ($settingsSchema->getParameters() as $parameterSchema) {
            $this->buildSettingsParameter($builder, $parameterSchema, $options);
        }
    }

    public function buildSettingsParameter(FormBuilderInterface $builder, ParameterSchema $parameter, ?array $options = null): void
    {
        $builder->add($parameter->getPropertyName(), $this->getFormTypeForParameter($parameter), $this->getFormOptions($parameter, $options));
    }

    /**
     * Gets the form type for the given parameter schema.
     * @param  ParameterSchema  $parameterSchema
     * @return string
     * @phpstan-return class-string<AbstractType>
     */
    public function getFormTypeForParameter(ParameterSchema $parameterSchema): string
    {
        //Check if an explicit form type is set, then it has priority
        if ($parameterSchema->getFormType() !== null) {
            return $parameterSchema->getFormType();
        }

        //Check if the parameter type has a default form type
        $parameterType = $this->parameterTypeRegistry->getParameterType($parameterSchema->getType());
        if ($parameterType instanceof ParameterTypeWithFormDefaultsInterface) {
            return $parameterType->getFormType($parameterSchema);
        }

        //If no form type is set, throw an exception
        throw new \RuntimeException(sprintf('No form type set for parameter "%s" in class "%s". You either have to explicitly define on on the property or use a parameter type defining a default one!',
            $parameterSchema->getName(), $parameterSchema->getClassName()));
    }

    /**
     * Gets the form options for the given parameter schema.
     * @param  ParameterSchema  $parameterSchema
     * @param  array  $options
     * @return array
     */
    public function getFormOptions(ParameterSchema $parameterSchema, array $options = []): array
    {
        $optionsResolver = new OptionsResolver();

        //Add the basic defaults
        $optionsResolver->setDefaults([
            'label' => $parameterSchema->getLabel(),
            'help' => $parameterSchema->getDescription(),
        ]);

        //Then add the defaults from the parameter type (if any)
        $parameterType = $this->parameterTypeRegistry->getParameterType($parameterSchema->getType());
        if ($parameterType instanceof ParameterTypeWithFormDefaultsInterface) {
            $parameterType->configureFormOptions($optionsResolver, $parameterSchema);
        }

        //Then add the defaults from the parameter schema
        $optionsResolver->setDefaults($parameterSchema->getFormOptions());


        //Finally resolve the options
        return $optionsResolver->resolve($options);
    }
}