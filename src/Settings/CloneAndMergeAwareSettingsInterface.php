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


namespace Jbtronics\SettingsBundle\Settings;

/**
 * If a settings class implements this interface, the methods in this interface are called by the SettingsCloner,
 * which allows you to customize the cloning and merging behavior of the settings object.
 */
interface CloneAndMergeAwareSettingsInterface
{
    /**
     * This method is called on the clone after it has been created. It allows to perform additional operations and copy
     * additional properties from the original instance to the clone.
     * @param  object  $original
     * @return void
     */
    public function afterSettingsClone(object $original): void;

    /**
     * This method is called on the original instance after the clone has been merged back into it. It allows to perform
     * additional operations and copy additional properties from the clone back to the original instance.
     * @param  object  $clone
     * @return void
     */
    public function afterSettingsMerge(object $clone): void;
}