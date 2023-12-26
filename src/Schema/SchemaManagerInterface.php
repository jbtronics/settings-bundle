<?php

namespace Jbtronics\UserConfigBundle\Schema;

interface SchemaManagerInterface
{
    /**
     * Checks if the given class is a config class.
     * @param  string  $className
     * @return bool
     */
    public function isConfigClass(string $className): bool;

    /**
     * Returns the configuration schema of the given class, which contains all metadata about the configuration class.
     * @param  string  $className
     * @return ConfigSchema
     */
    public function getSchema(string $className): ConfigSchema;
}