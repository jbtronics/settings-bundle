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

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Jbtronics\SettingsBundle\Schema\SettingsSchema;

/**
 * This is the base interface for all config entry types, defining how to map the value in the config class to a
 * normalized form, which can be serialized and stored in the storage provider.
 */
interface ParameterTypeInterface
{
    /**
     * Convert the given PHP value to a normalized form, which can be serialized and stored in the storage provider.
     * Only values which can be JSON encoded are allowed as return value.
     * @param  mixed  $value
     * @param  string $parameterName The name of the property which is currently handled
     * @param  SettingsSchema  $configSchema The schema of the currently handled config class
     * @return int|string|float|bool|array|null
     */
    public function convertPHPToNormalized(mixed $value, SettingsSchema $configSchema, string $parameterName): int|string|float|bool|array|null;

    /**
     * Convert the given normalized value to a PHP value.
     * @param  int|string|float|bool|array|null  $value
     * @param  string  $parameterName  The name of the property which is currently handled
     * @param  SettingsSchema  $configSchema  The schema of the currently handled config class
     * @return mixed
     */
    public function convertNormalizedToPHP(int|string|float|bool|array|null $value, SettingsSchema $configSchema, string $parameterName): mixed;
}