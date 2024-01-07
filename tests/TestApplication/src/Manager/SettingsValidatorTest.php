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

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Manager;

use Jbtronics\SettingsBundle\Manager\SettingsValidator;
use Jbtronics\SettingsBundle\Manager\SettingsValidatorInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\ValidatableSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SettingsValidatorTest extends KernelTestCase
{
    private SettingsValidatorInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsValidatorInterface::class);
    }

    public function testValidateValidObject(): void
    {
        $valid_settings = new ValidatableSettings();
        $errors = $this->service->validate($valid_settings);

        //The settings should be valid, so there should be no errors
        $this->assertEmpty($errors);
    }

    public function testValidateInvalidObject(): void
    {
        $settings = new ValidatableSettings();
        //Make the first property invalid
        $settings->value1 = '';

        $errors = $this->service->validate($settings);
        //There should be exactly one error with the key 'value1'
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('value1', $errors);

        //Make the second property invalid
        $settings->value2 = -10;

        $errors = $this->service->validate($settings);
        //Now we should have two errors
        //$this->assertCount(2, $errors);

        //In this form
        $this->assertEquals([
            'value1' => ['Value must not be blank'],
            'value2' => ['Value must be greater than 0']
        ], $errors);
    }
}