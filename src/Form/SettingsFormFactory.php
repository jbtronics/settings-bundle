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
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class SettingsFormFactory implements SettingsFormFactoryInterface
{
    public function __construct(
        private readonly SettingsManagerInterface $settingsManager,
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsFormBuilderInterface $settingsFormBuilder,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function createSettingsForm(string $settingsName): FormInterface
    {
        return $this->createSettingsFormBuilder($settingsName)->getForm();
    }

    public function createSettingsFormBuilder(string|object $settings, ?array $groups = null, array $formOptions = []): FormBuilderInterface
    {
        $settingsMetadata = $this->metadataManager->getSettingsMetadata($settings);

        //Ensure that the embedded settings do not cause a infinite loop
        if ($this->checkForCircularEmbedded($settingsMetadata, $groups)) {
            throw new \LogicException(
                sprintf('The settings class "%s" have a circular embedded settings structure, which would cause an infinite loop while form building. Use the groups option to only render a subset of the embedded settings to prevent circular loops.',
                    $settingsMetadata->getClassName(),
                ));
        }

        //If a string is passed, get the current settings instance from the settings manager
        //This is deprecated
        if (is_string($settings)) {
            trigger_deprecation('jbtronics/settings-bundle', '2.0', 'Passing a string as settings name to createSettingsFormBuilder() is deprecated. Pass the settings instance directly instead.');
            $settings = $this->settingsManager->get($settings);
        }

        $formOptions['settings_metadata'] = $settingsMetadata;

        $formBuilder = $this->formFactory->createBuilder(data: $settings, options: $formOptions);
        $this->settingsFormBuilder->buildSettingsForm($formBuilder, $settingsMetadata, [], groups: $groups);

        return $formBuilder;
    }

    public function createMultiSettingsFormBuilder(
        array $settings,
        ?array $groups = null,
        array $formOptions = [],
        array $rootFormOptions = []
    ): FormBuilderInterface
    {
        $deprecation_triggered = false;

        $formBuilder = $this->formFactory->createBuilder(options: $rootFormOptions);
        //The form is a compound form, so we need to set this to true
        $formBuilder->setCompound(true);
        foreach ($settings as $setting) {
            $settingsMetadata = $this->metadataManager->getSettingsMetadata($setting);

            //Ensure that the embedded settings do not cause a infinite loop
            if ($this->checkForCircularEmbedded($settingsMetadata, $groups)) {
                throw new \LogicException(
                    sprintf('The settings class "%s" have a circular embedded settings structure, which would cause an infinite loop while form building. Use the groups option to only render a subset of the embedded settings to prevent circular loops.',
                        $settingsMetadata->getClassName(),
                    ));
            }

            //If a string is passed, get the current settings instance from the settings manager
            //This is deprecated
            if (is_string($setting)) {
                if (!$deprecation_triggered) { //Only trigger the deprecation once
                    trigger_deprecation('jbtronics/settings-bundle', '2.0',
                        'Passing a string as settings name to createSettingsFormBuilder() is deprecated. Pass the settings instance directly instead.');
                }
                $deprecation_triggered = true;
                $setting = $this->settingsManager->get($setting);
            }

            $formOptions['settings_metadata'] = $settingsMetadata;

            //Create sub form builder for the settings with the name of the settings class
            $subBuilder = $this->formFactory->createNamedBuilder($settingsMetadata->getName(),
                data: $setting, options: $formOptions);

            //Configure the sub form builder
            $this->settingsFormBuilder->buildSettingsForm($subBuilder, $settingsMetadata, [], groups: $groups);

            //And add it to the main form builder
            $formBuilder->add($subBuilder);
        }

        return $formBuilder;
    }

    /**
     * Checks if the given settings have a circular embedded settings structure, which would cause an infinite loop.
     * It is checked for the given groups, if null all embedded settings are checked.
     * @param  SettingsMetadata  $metadata
     * @param  array|null  $groups
     * @param  array  $already_checked
     * @return bool
     */
    public function checkForCircularEmbedded(SettingsMetadata $metadata, ?array $groups = null, array $already_checked = []): bool
    {
        $already_checked[] = $metadata->getClassName();

        $embeddedToRender = $groups === null ? $metadata->getEmbeddedSettings() : $metadata->getEmbeddedSettingsWithOneOfGroups($groups);

        foreach ($embeddedToRender as $embeddedMetadata) {
            if (in_array($embeddedMetadata->getTargetClass(), $already_checked, true)) {
                return true;
            }
            $already_checked[] = $embeddedMetadata->getTargetClass();
            $embeddedMeta = $this->metadataManager->getSettingsMetadata($embeddedMetadata->getTargetClass());
            if ($this->checkForCircularEmbedded($embeddedMeta, $groups, $already_checked)) {
                return true;
            }
        }

        return false;
    }
}