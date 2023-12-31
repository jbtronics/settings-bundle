<?php

namespace Jbtronics\SettingsBundle\Manager;

/**
 * This services is responsible for validating the settings values, that they are valid for the given
 * settings class.
 */
interface SettingsValidatorInterface
{
    /**
     * Validates the given settings class and returns an array of errors,
     * in the format of: ['property' => ['error1', 'error2', ...], ...]
     */
    public function validate(object $settings): array;
}