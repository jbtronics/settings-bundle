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

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Metadata\MetadataManager;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * This service provides a settings validator using the Symfony Validator component.
 */
class SettingsValidator implements SettingsValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly MetadataManagerInterface $metadataManager,
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

    public function validateRecursively(object $settings): array
    {
        $already_checked = [];

       return $this->validateRecursivelyInternal($settings, $already_checked);
    }

    private function validateRecursivelyInternal(object $settings, array &$already_checked): array
    {
        //Use the metadata manager to get the nested embedded settings classes
        $metadata = $this->metadataManager->getSettingsMetadata($settings);

        //Validate the settings class and all embedded settings classes
        $errors_per_class = [];

        //Validate the settings class itself
        $errors = $this->validate($settings);
        if (count($errors) > 0) { //Create the key only if there are errors
            $errors_per_class[$metadata->getClassName()] = $errors;
        }
        $already_checked[$metadata->getClassName()] = true;

        //Validate all embedded settings classes
        foreach ($metadata->getEmbeddedSettings() as $embeddedSetting) {
            //If the embedded setting class was already checked, we can skip it
            if (isset($already_checked[$embeddedSetting->getTargetClass()])) {
                continue;
            }

            //Otherwise extract the embedded object and validate it
            $embedded = PropertyAccessHelper::getProperty($settings, $embeddedSetting->getPropertyName());

            $errors_per_class = array_merge_recursive($errors_per_class, $this->validateRecursivelyInternal($embedded, $already_checked));
        }

        return $errors_per_class;

    }
}