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

namespace Jbtronics\SettingsBundle\Manager;

/**
 * This services is responsible for validating the settings values, that they are valid for the given
 * settings class.
 */
interface SettingsValidatorInterface
{
    /**
     * Validates the given settings class and returns an array of errors,
     * in the format of: ['property' => ['error1', 'error2', ...], ...]
     * The array is empty if the settings are valid.
     * @param  object  $settings
     * @return array
     * @phpstan-return array<string, array<string>>
     */
    public function validate(object $settings): array;

    /**
     * Validates the given settings class and all their nested embedded settings classes.
     * Returns an array of errors, in the format of: ['class' => ['property' => ['error1', 'error2', ...], ...], ...]
     * The array is empty if the settings are valid.
     * @param  object  $settings
     * @return array<string, array<string, array<string>>>
     */
    public function validateRecursively(object $settings): array;
}