<?php

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Schema\SettingsSchema;

interface SettingsHydratorInterface
{
    /**
     * Hydrate the given settings object with the values from the storage provider.
     * @param  object  $settings The settings object to hydrate
     * @param  SettingsSchema  $schema The schema of the settings object to use
     * @return object The hydrated settings object
     */
    public function hydrate(object $settings, SettingsSchema $schema): object;

    /**
     * Persist the given settings object to the storage provider.
     * @param  object  $settings The settings object to persist
     * @param  SettingsSchema  $schema The schema of the settings object to use
     * @return object The persisted settings object
     */
    public function persist(object $settings, SettingsSchema $schema): object;
}