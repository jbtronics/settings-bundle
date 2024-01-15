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


namespace Jbtronics\SettingsBundle\Metadata;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;

/**
 * The service implementing this interface is responsible for guessing the parameter type of a given settings property.
 */
interface ParameterTypeGuesserInterface
{
    /**
     * Returns the guessed parameter type for the given property.
     * @param  \ReflectionProperty  $property
     * @return string|null The parameter type class name or null if no type could be guessed
     * @phpstan-return class-string<ParameterTypeInterface>|null
     */
    public function guessParameterType(\ReflectionProperty $property): ?string;

    /**
     * Tries to guess the options for the given property, for configuring the parameter schema.
     * @param  \ReflectionProperty  $property
     * @return array|null The extra options or null if no extra options could be guessed
     */
    public function guessOptions(\ReflectionProperty $property): ?array;
}