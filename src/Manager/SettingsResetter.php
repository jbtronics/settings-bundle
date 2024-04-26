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
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;

/**
 * @internal Use the SettingsManager to reset settings
 */
final class SettingsResetter implements SettingsResetterInterface
{
    public function __construct(private readonly EnvVarValueResolverInterface $envVarValueResolver)
    {
    }

    public function resetSettings(object $settings, SettingsMetadata $metadata): object
    {
        //Ensure that the given metadata is for the given settings class
        if (is_a($settings, $metadata->getClassName()) === false) {
            throw new \InvalidArgumentException(
                sprintf('The given settings class "%s" is not the same as the class "%s" of the given metadata!', get_class($settings), $metadata->getClassName())
            );
        }

        //First we reset the properties of the settings to their declared default values

        //For this we iterate over all declared parameters of the settings class and reset them
        foreach ($metadata->getParameters() as $parameterMetadata) {
            $this->resetProperty($settings, $parameterMetadata->getPropertyName());
        }

        //Afterward we call the handler in the class if it implements the ResettableSettingsInterface
        if (is_a($settings, ResettableSettingsInterface::class)) {
            $settings->resetToDefaultValues();
        }

        //Apply the environment variables to the instance
        $this->applyEnvVars($metadata, $settings);

        //The instance should now be resetted to its default values
        return $settings;
    }

    /**
     * Reset the given property of the settings class to its default value
     * @param  object  $settings
     * @param  string  $propertyName
     * @return void
     */
    private function resetProperty(object $settings, string $propertyName): void
    {
        //Retrieve the reflection property
        $reflectionProperty = PropertyAccessHelper::getAccessibleReflectionProperty($settings, $propertyName);

        //Check if the property has a default value or is nullable
        //A type without a type is nullable by default
        if (!($reflectionProperty->hasDefaultValue() || ($reflectionProperty->getType()?->allowsNull() ?? true))) {
            //Check if the property defines a resettable interface, which would allow to reset the property, then we
            //can skip this property and do the reset later
            if (is_a($settings, ResettableSettingsInterface::class)) {
                return;
            }

            //If not we throw an exception, that this property cannot be reset
            throw new \LogicException(
                sprintf('The property "%s" of the class "%s" has no default value and is not nullable. Therefore it cannot be reset without the settings class implementing the ResettableSettingsInterface!', $propertyName, get_class($settings))
            );
        }

        //Try to retrieve the default value from the reflection property
        $defaultValue = $reflectionProperty->getDefaultValue();

        //And set it to the property
        $reflectionProperty->setValue($settings, $defaultValue);
    }

    private function applyEnvVars(SettingsMetadata $metadata, object $settings): void
    {
        //Iterate over all parameters, which should be set by an environment variable and set them
        //Here it does not matter, which mode is set. The differences only matter for hydration/dehydration
        foreach ($metadata->getParametersWithEnvVar() as $parameterMetadata) {
            //Skip the parameter, if the environment variable is not set
            if (!$this->envVarValueResolver->hasValue($parameterMetadata)) {
                continue;
            }

            //Retrieve the value from the environment variable
            $value = $this->envVarValueResolver->getValue($parameterMetadata);

            //Set the value to the property
            PropertyAccessHelper::setProperty($settings, $parameterMetadata->getPropertyName(), $value);
        }
    }

    public function newInstance(SettingsMetadata $metadata): object
    {
        $settingsClass = $metadata->getClassName();

        $reflectionClass = new \ReflectionClass($settingsClass);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        //If the class is resettable, we call the reset method
        if (is_a($settingsClass, ResettableSettingsInterface::class, true)) {
            $instance->resetToDefaultValues();
        }

        //Apply the environment variables to the instance
        $this->applyEnvVars($metadata, $instance);


        return $instance;
    }
}