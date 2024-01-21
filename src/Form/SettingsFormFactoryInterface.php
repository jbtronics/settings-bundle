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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * This service allows to create forms and form builders for settings.
 */
interface SettingsFormFactoryInterface
{
    /**
     * Creates a form builder for the given settings. The form builder data is set to the current settings instance.
     * You can add additional form elements to the form builder.
     * @param  string  $settingsName The name of the settings to create the form builder for (either class name or short name).
     * @param  array|null  $groups The parameter groups to render. If null, all parameters are rendered.
     * @param  array  $formOptions Options to pass to the form builder.
     * @return FormBuilderInterface The created form builder.
     */
    public function createSettingsFormBuilder(string $settingsName, ?array $groups = null, array $formOptions = []): FormBuilderInterface;

    /**
     * Creates a form builder for multiple settings. Each setting as a sub form in the root form builder.
     * The form builder data is set to the current settings instance. You can add additional form elements to the form builder.
     * @param  array  $settingsNames The names of the settings to create the form builder for (either class name or short name).
     * @param  array|null  $groups The parameter groups to render. If null, all parameters are rendered.
     * @param  array  $formOptions Options to pass to the form builder.
     * @return FormBuilderInterface The root form builder.
     */
    public function createMultiSettingsFormBuilder(array $settingsNames, ?array $groups = null, array $formOptions = []): FormBuilderInterface;


}