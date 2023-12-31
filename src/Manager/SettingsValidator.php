<?php

namespace Jbtronics\SettingsBundle\Manager;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This service provides a settings validator using the Symfony Validator component.
 */
class SettingsValidator implements SettingsValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {

    }

    public function validate(object $settings): array
    {
        //Validate the settings class using the Symfony Validator component
        $errors = $this->validator->validate($settings);

        //Convert the Symfony errors to an array of errors in the format of: ['property' => ['error1', 'error2', ...], ...]
        $errors_by_property = [];
        foreach ($errors as $error) {
            $errors_by_property[$error->getPropertyPath()][] = $error->getMessage();
        }

        return $errors_by_property;
    }
}