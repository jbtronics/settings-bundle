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

namespace Jbtronics\SettingsBundle\Metadata;

interface MetadataManagerInterface
{
    /**
     * Checks if the given class is a config class.
     * @param  string|object  $className The configuration class, to check. This can either be a class string, the short name, or an instance of the class.
     * @phpstan-param class-string|object $className
     * @return bool
     */
    public function isSettingsClass(string|object $className): bool;

    /**
     * Returns the metadata of the given settings class, which contains all info about the configuration class.
     * @template T of object
     * @param  string|object  $className The configuration class, to get the metadata for. This can either be a class string, the short name, or an instance of the class.
     * @phpstan-param class-string<T>|T $className
     * @return SettingsMetadata
     * @phpstan-return SettingsMetadata<T>
     */
    public function getSettingsMetadata(string|object $className): SettingsMetadata;
}