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


namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Schema\ParameterSchema;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * If a parameter type implements this interface, it defines default values for form rendering.
 */
interface ParameterTypeWithFormDefaultsInterface
{
    /**
     * Returns the default form type for this parameter type.
     * @param  ParameterSchema  $parameterSchema The parameter schema of the parameter to configure
     * @return string The form type class name
     * @phpstan-return class-string<AbstractType>
     */
    public function getFormType(ParameterSchema $parameterSchema): string;

    /**
     * Configure the defaults form type for this parameter type.
     * @param  OptionsResolver  $resolver The options resolver for the form type
     * @param  ParameterSchema  $parameterSchema The parameter schema of the parameter to configure
     * @return void
     */
    public function configureFormOptions(OptionsResolver $resolver, ParameterSchema $parameterSchema): void;
}