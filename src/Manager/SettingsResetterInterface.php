<?php

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Schema\SettingsSchema;

/**
 * This interface is used to reset a settings instance to their default values.
 */
interface SettingsResetterInterface
{
    /**
     * Resets the settings instance to their default values.
     * The instance is returned.
     * @param  object  $settings
     * @param SettingsSchema $schema The schema, that should be used to reset the settings
     * @return object
     */
    public function resetSettings(object $settings, SettingsSchema $schema): object;
}