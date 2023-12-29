<?php

namespace Jbtronics\SettingsBundle\Settings;

/**
 * This interface is used to reset a settings instance to their default values.
 * This can also be used to implement more complex default values, which can not be set directly
 * in the property declaration.
 */
interface ResettableSettingsInterface
{
    /**
     * Reset the values of this settings instance to their default values.
     * This method is called by the SettingsResetter after the properties with a default value are set to their declared
     * default value. Therefore, you normally only need to reset the more complex properties without a default value here.
     * @return void
     */
    public function resetToDefaultValues(): void;
}