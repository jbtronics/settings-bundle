<?php

namespace Jbtronics\UserConfigBundle\Schema;

use Jbtronics\UserConfigBundle\Metadata\ConfigClass;

class ConfigSchema
{
    public function __construct(
        private readonly string $className,
        private readonly ConfigClass $classAttribute,
        private readonly array $configEntryProperties,
    )
    {

    }

    /**
     * Returns the class name of the configuration class, which is managed by this schema.
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Returns the config class attribute associated with this schema.
     * @return ConfigClass
     */
    public function getConfigClassAttribute(): ConfigClass
    {
        return $this->classAttribute;
    }
}