<?php

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
