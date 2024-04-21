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


namespace Jbtronics\SettingsBundle\Manager;

interface SettingsClonerInterface
{
    /**
     * Creates a copy of the given, by copying all parameters of the settings object to a new instance.
     * The instance is not tracked by the SettingsManager!
     * If the settings object implements the CloneAndMergeAwareSettingsInterface, the afterSettingsClone method is called
     * on the clone after the internal cloning logic has been executed.
     * For all embedded settings objects, lazy loaded clones are created.
     * @param  object  $settings
     * @return object The cloned settings instance
     */
    public function createClone(object $settings): object;

    /**
     * Merges the given copy into the given settings object, by copying all parameters of the copy to the settings object.
     * If the settings object implements the CloneAndMergeAwareSettingsInterface, the afterSettingsMerge method is called.
     * If the $recursive parameter is set to true, the merge operation is also executed on all embedded settings objects,
     * otherwise only the top level settings object is merged.
     * @param  object  $copy
     * @param  object  $into
     * @param  bool  $recursive
     * @return object The $into settings instance with the values of the $copy instance merged into it
     */
    public function mergeCopy(object $copy, object $into, bool $recursive = true): object;
}