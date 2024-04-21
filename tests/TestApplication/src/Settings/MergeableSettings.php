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


namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings;

use Jbtronics\SettingsBundle\Settings\CloneAndMergeAwareSettingsInterface;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Settings\SettingsTrait;

#[Settings]
class MergeableSettings implements ResettableSettingsInterface, CloneAndMergeAwareSettingsInterface
{
    use SettingsTrait;

    #[SettingsParameter]
    public bool $bool = true;

    #[SettingsParameter]
    public \DateTime $dateTime1;

    #[SettingsParameter(cloneable: false)]
    public \DateTime $dateTime2;

    /**
     * @var object|null These properties get filled with the argument, if the corresponding method is called.
     */
    public ?object $mergeCalled = null;

    public ?object $cloneCalled = null;



    public function resetToDefaultValues(): void
    {
        $this->dateTime1 = new \DateTime();
        $this->dateTime2 = new \DateTime();
    }

    public function afterSettingsClone(object $original): void
    {
        $this->cloneCalled = $original;
    }

    public function afterSettingsMerge(object $clone): void
    {
        $this->mergeCalled = $clone;
    }
}