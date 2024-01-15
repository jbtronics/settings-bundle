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

namespace Jbtronics\SettingsBundle\Tests\Schema;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\EnumType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Metadata\ParameterTypeGuesser;
use Jbtronics\SettingsBundle\Metadata\ParameterTypeGuesserInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\GuessableSettings;
use PHPUnit\Framework\TestCase;

class ParameterTypeGuesserTest extends TestCase
{

    private ParameterTypeGuesserInterface $parameterTypeGuesser;

    protected function setUp(): void
    {
        $this->parameterTypeGuesser = new ParameterTypeGuesser();
    }

    public function testGuessParameterType(): void
    {
        $settings = new GuessableSettings();

        //Test that null is returned for non-guessable types
        $this->assertNull($this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'complexType')
        ));
        $this->assertNull($this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'stdClass')
        ));

        //Test that the correct type is returned for guessable types
        $this->assertEquals(BoolType::class, $this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'bool')
        ));
        $this->assertEquals(IntType::class, $this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'int')
        ));
        $this->assertEquals(StringType::class, $this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'string')
        ));
        $this->assertEquals(EnumType::class, $this->parameterTypeGuesser->guessParameterType(
            PropertyAccessHelper::getAccessibleReflectionProperty($settings, 'enum')
        ));
    }

    public function testGuessExtraOptions(): void
    {
        //For non-guessable types, null should be returned
        $this->assertNull($this->parameterTypeGuesser->guessOptions(
            PropertyAccessHelper::getAccessibleReflectionProperty(new GuessableSettings(), 'complexType')
        ));

        //For most types, which has no extra options, null should be returned
        $this->assertNull($this->parameterTypeGuesser->guessOptions(
            PropertyAccessHelper::getAccessibleReflectionProperty(new GuessableSettings(), 'bool')
        ));

        //For enum types, the class name should be returned
        $this->assertEquals([
            'class' => TestEnum::class,
        ], $this->parameterTypeGuesser->guessOptions(
            PropertyAccessHelper::getAccessibleReflectionProperty(new GuessableSettings(), 'enum')
        ));
    }

}
