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

namespace Jbtronics\SettingsBundle\Settings;

/**
 * This attribute marks a class as a settings class, whose values are managed by the UserConfigBundle.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Settings
{
    /**
     * @param  string|null  $name  The (short) name of the settings class. If not set, the lower case class name without the "Settings" suffix is used.
     * @param  string|null  $storageAdapter  The storage adapter to use for this settings class. If not set, the default storage adapter is used.
     * @param  array  $storageAdapterOptions  An array of options, which should be passed to the storage adapter.
     * @param  string[]|null  $groups  An array of groups, which the parameters of this settings class should belong too, if they are not explicitly set.
     * @param  int|null  $version  The current version of the settings class. Null, if the settings should not be versioned. If set, you have to set a migrator service too.
     * @param  string|null  $migrationService  The service id of the migrator service, which should be used to migrate the settings from one version to another.
     */
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $storageAdapter = null,
        public readonly array $storageAdapterOptions = [],
        public readonly array|null $groups = null,
        public readonly int|null $version = null,
        public readonly string|null $migrationService = null,
    )
    {

    }
}