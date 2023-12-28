<?php

namespace Jbtronics\SettingsBundle\Schema;

interface SchemaManagerInterface
{
    /**
     * Checks if the given class is a config class.
     * @param  string  $className
     * @return bool
     */
    public function isSettingsClass(string $className): bool;

    /**
     * Returns the configuration schema of the given class, which contains all metadata about the configuration class.
     * @param  string  $className
     * @return SettingsSchema
     */
    public function getSchema(string $className): SettingsSchema;
}