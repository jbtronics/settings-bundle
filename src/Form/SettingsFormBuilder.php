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

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Metadata\EmbeddedSettingsMetadata;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeWithFormDefaultsInterface;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Translation\TranslatableMessage;

final class SettingsFormBuilder implements SettingsFormBuilderInterface
{
    public function __construct(
        private readonly ParameterTypeRegistryInterface $parameterTypeRegistry,
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsManagerInterface $settingsManager,
    )
    {
    }

    public function buildSettingsForm(
        FormBuilderInterface $builder,
        SettingsMetadata $metadata,
        array $options = [],
        ?array $groups = null
    ): void
    {
        //Either use all parameters or only the ones in the given groups
        $parametersToRender = $groups === null ? $metadata->getParameters() : $metadata->getParametersWithOneOfGroups($groups);

        //Add the parameters to the form
        $embeddedToRender = $groups === null ? $metadata->getEmbeddedSettings() : $metadata->getEmbeddedSettingsWithOneOfGroups($groups);

        foreach ($embeddedToRender as $embeddedMetadata) {
            $this->addEmbeddedSettingsSubForm($builder, $embeddedMetadata, $options, groups: $groups);
        }

        foreach ($parametersToRender as $parameterMetadata) {
            $this->addSettingsParameter($builder, $parameterMetadata, $options);
        }
    }

    public function addSettingsParameter(FormBuilderInterface $builder, ParameterMetadata $parameter, array $options = []): void
    {
        $builder->add($parameter->getPropertyName(), $this->getFormTypeForParameter($parameter), $this->getFormOptions($parameter, $options));
    }

    public function addEmbeddedSettingsSubForm(FormBuilderInterface $builder, EmbeddedSettingsMetadata $embedded, array $options = [], ?array $groups = null): FormBuilderInterface
    {
        //Set the right data class, so that the access methods of the settings can be properly detecty by symfony/forms
        $options['data_class'] = $embedded->getTargetClass();
        //Set constraints to validate all sub settings
        $options['constraints'] = [
            new Valid()
        ];

        $embeddedMeta = $this->metadataManager->getSettingsMetadata($embedded->getTargetClass());

        //If a label is set, use it as the label of the embedded form
        if ($embedded->getLabel() !== null) {
            $options['label'] = $embedded->getLabel();
        } elseif ($embeddedMeta->getLabel() !== null) {
            $options['label'] = $embeddedMeta->getLabel();
        }

        //If a description is set, use it as the help text of the embedded form
        if ($embedded->getDescription() !== null) {
            $options['help'] = $embedded->getDescription();
        } elseif ($embeddedMeta->getDescription() !== null) {
            $options['help'] = $embeddedMeta->getDescription();
        }

        //If form settings are set on the embedded settings, use them
        if ($embedded->getFormOptions() !== null) {
            $options = array_merge($options, $embedded->getFormOptions());
        }

        $options['embedded_settings_metadata'] = $embedded;
        $options['settings_metadata'] = $embeddedMeta;

        $subBuilder = $builder->getFormFactory()->createNamedBuilder($embedded->getPropertyName(), options: $options);


        $this->buildSettingsForm($subBuilder, $embeddedMeta, groups: $groups);

        $builder->add($subBuilder);

        return $subBuilder;
    }

    /**
     * Gets the form type for the given parameter metadata.
     * @param  ParameterMetadata  $parameterMetadata
     * @return string
     * @phpstan-return class-string<AbstractType>
     */
    public function getFormTypeForParameter(ParameterMetadata $parameterMetadata): string
    {
        //Check if an explicit form type is set, then it has priority
        if ($parameterMetadata->getFormType() !== null) {
            return $parameterMetadata->getFormType();
        }

        //Check if the parameter type has a default form type
        $parameterType = $this->parameterTypeRegistry->getParameterType($parameterMetadata->getType());
        if ($parameterType instanceof ParameterTypeWithFormDefaultsInterface) {
            return $parameterType->getFormType($parameterMetadata);
        }

        //If no form type is set, throw an exception
        throw new \RuntimeException(sprintf('No form type set for parameter "%s" in class "%s". You either have to explicitly define on on the property or use a parameter type defining a default one!',
            $parameterMetadata->getName(), $parameterMetadata->getClassName()));
    }

    /**
     * Gets the form options for the given parameter metadata.
     * @param  ParameterMetadata  $parameterMetadata
     * @param  array  $options The parameters passed to the options resolver
     * @return array The resolved options
     */
    public function getFormOptions(ParameterMetadata $parameterMetadata, array $options = []): array
    {
        $optionsResolver = new OptionsResolver();

        //Add the basic defaults
        $optionsResolver->setDefaults([
            'label' => $parameterMetadata->getLabel(),
            'help' => $parameterMetadata->getDescription(),
            //By default, the parameter is required if the property is not nullable
            'required' => !$parameterMetadata->isNullable(),
            'parameter_metadata' => $parameterMetadata,
            'settings_metadata' => $this->metadataManager->getSettingsMetadata($parameterMetadata->getClassName()),
        ]);

        //Then add the defaults from the parameter type (if any)
        $parameterType = $this->parameterTypeRegistry->getParameterType($parameterMetadata->getType());
        if ($parameterType instanceof ParameterTypeWithFormDefaultsInterface) {
            $parameterType->configureFormOptions($optionsResolver, $parameterMetadata);
        }

        //Then add the defaults from the parameter metadata
        $optionsResolver->setDefaults($parameterMetadata->getFormOptions());

        //Finally resolve the options
        $resolved = $optionsResolver->resolve($options);

        //Check if the given parameter is overridden by an environment variable
        if ($this->settingsManager->isEnvVarOverwritten($parameterMetadata->getClassName(), $parameterMetadata)) {
            //Then disable the field in the form
            $resolved['disabled'] = true;
            //And override the help text to show that the value is overridden
            $resolved['help'] = new TranslatableMessage('form.parameter_env_var_overridden.help', [
                '%env_var%' => $parameterMetadata->getBaseEnvVar(),
            ], domain: 'JbtronicsSettingsBundle');
        }

        return $resolved;
    }
}