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

use InvalidArgumentException;

/**
 * This interface defines contracts to get all defined configuration classes.
 */
interface SettingsRegistryInterface
{
    /**
     * Returns all settings classes, defined in the application.
     * @return string[] The settings classes in the format [short_name => class_name]
     * @phpstan-return array<string, class-string>
     */
    public function getSettingsClasses(): array;

    /**
     * Returns the settings class name for the given settings (short) name.
     * @param  string  $name The name of the settings class
     * @return string The settings class for the given short name
     * @throws InvalidArgumentException If no settings class with the given name exists.
     */
    public function getSettingsClassByName(string $name): string;
}