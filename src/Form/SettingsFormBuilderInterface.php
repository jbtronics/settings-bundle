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
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This service builds settings forms based on form metadata.
 */
interface SettingsFormBuilderInterface
{
    /**
     * Adds the form field for the given parameter to the form builder.
     * The parameter is described by its metadata.
     * @param  FormBuilderInterface  $builder The form builder to add the form field to
     * @param  ParameterMetadata  $parameter The parameter to add to the form
     * @param  array  $options The options to pass to the form field, allows to override the default options
     * @return void
     */
    public function addSettingsParameter(FormBuilderInterface $builder, ParameterMetadata $parameter, array $options = []): void;

    /**
     * Builds the form for the given settings. It adds all parmeters of the settings to the given form builder.
     * The settings are described by their metadata. It is possible to only add parameters, with certain groups by passing
     * an array of groups to the $groups parameter.
     * @param  FormBuilderInterface  $builder The form builder to add the form fields to
     * @param  SettingsMetadata  $metadata The metadata of the settings to add to the form
     * @param  array  $options The options to pass to the form fields, allows to override the default options
     * @param  array|null  $groups The groups to add to the form, if null all parameters are added
     * @return void
     */
    public function buildSettingsForm(FormBuilderInterface $builder, SettingsMetadata $metadata, array $options = [], ?array $groups = null): void;

    /**
     * Add the given embedded settings as a sub form to the given form builder.
     * The embedded settings are described by their metadata. In the process a new form builder for the sub form is created
     * and returned.
     * @param  FormBuilderInterface  $builder
     * @param  EmbeddedSettingsMetadata  $embedded
     * @param  array  $options The options to pass to the newly created sub form builder
     * @param  array|null  $groups The groups to add to the form, if null all parameters are added
     * @return FormBuilderInterface The newly created sub form builder
     */
    public function addEmbeddedSettingsSubForm(FormBuilderInterface $builder, EmbeddedSettingsMetadata $embedded, array $options = [], ?array $groups = null): FormBuilderInterface;
}