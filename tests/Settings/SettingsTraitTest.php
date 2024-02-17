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

namespace Jbtronics\SettingsBundle\Tests\Settings;

use Jbtronics\SettingsBundle\Settings\SettingsTrait;
use PHPUnit\Framework\TestCase;

class TestClass
{
    use SettingsTrait;
}

class SettingsTraitTest extends TestCase
{

    private TestClass $instance;

    public function setUp(): void
    {
        $reflClass = new \ReflectionClass(TestClass::class);
        $this->instance = $reflClass->newInstanceWithoutConstructor();
    }

    public function testPreventConstructor(): void
    {
        $this->expectException(\Error::class);
        //Invalid instantiation
        new TestClass();
    }

    public function testPreventClone(): void
    {
        $this->expectException(\Error::class);
        //Invalid clone
        clone $this->instance;
    }
}
