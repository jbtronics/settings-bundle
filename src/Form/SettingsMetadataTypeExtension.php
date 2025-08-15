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

use Jbtronics\SettingsBundle\Metadata\EmbeddedSettingsMetadata;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This class adds metadata to the settings form view, so that you users can customize the form rendering further in
 * templates.
 * The metadata will be available in the form view under the `settings_metadata` variable.
 * If an env variable is set for the parameter, it will also be available under the `settings_envvar` variable.
 */
class SettingsMetadataTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        //We affect all form types, so that we can add the metadata to all forms
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('settings_metadata');
        $resolver->setAllowedTypes('settings_metadata', ['null', ParameterMetadata::class, SettingsMetadata::class, EmbeddedSettingsMetadata::class]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        //Only become active, if the settings_metadata option is set
        if (isset($options['settings_metadata'])) {
            $view->vars['settings_metadata'] = $options['settings_metadata'];

            if ($options['settings_metadata'] instanceof SettingsMetadata) {
                $view->vars['settings_metadata_type'] = 'settings';
            } elseif ($options['settings_metadata'] instanceof EmbeddedSettingsMetadata) {
                $view->vars['settings_metadata_type'] = 'embedded';
            } elseif ($options['settings_metadata'] instanceof ParameterMetadata) {
                $view->vars['settings_metadata_type'] = 'parameter';
            } else {
                throw new \InvalidArgumentException('The "settings_metadata" option must be an instance of SettingsMetadata, EmbeddedSettingsMetadata or ParameterMetadata.');
            }

            //Add a special env variable to the form view
            if ($options['settings_metadata'] instanceof ParameterMetadata) {
                $view->vars['settings_envvar'] = $options['settings_metadata']->getBaseEnvVar();
            }
        }
    }
}