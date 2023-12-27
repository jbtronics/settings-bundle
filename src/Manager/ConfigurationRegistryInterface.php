<?php

namespace Jbtronics\UserConfigBundle\Manager;

/**
 * This interface defines contracts to get all defined configuration classes.
 */
interface ConfigurationRegistryInterface
{
    /**
     * Returns all configuration classes, defined in the application.
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getConfigClasses(): array;
}