<?php

namespace Jbtronics\SettingsBundle\Schema;

interface SchemaManagerInterface
{
    /**
     * Checks if the given class is a config class.
     * @param  string|object  $className
     * @return bool
     */
    public function isSettingsClass(string|object $className): bool;

    /**
     * Returns the configuration schema of the given class, which contains all metadata about the configuration class.
     * @param  string|object  $className
     * @return SettingsSchema
     */
    public function getSchema(string|object $className): SettingsSchema;
}