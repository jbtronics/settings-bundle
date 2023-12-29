<?php

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;

class SettingsResetter implements SettingsResetterInterface
{
    public function resetSettings(object $settings, SettingsSchema $schema): object
    {
        //Ensure that the schema is for the given settings class
        if (is_a($settings, $schema->getClassName()) === false) {
            throw new \InvalidArgumentException(
                sprintf('The given settings class "%s" is not the same as the class "%s" of the given schema!', get_class($settings), $schema->getClassName())
            );
        }

        //First we reset the properties of the settings to their declared default values

        //For this we iterate over all declared parameters of the settings class and reset them
        foreach ($schema->getParameters() as $propertySchema) {
            $this->resetProperty($settings, $propertySchema->getPropertyName());
        }

        //Afterward we call the handler in the class if it implements the ResettableSettingsInterface
        if (is_a($settings, ResettableSettingsInterface::class)) {
            $settings->resetToDefaultValues();
        }

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
}