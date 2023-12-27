<?php

namespace Jbtronics\SettingsBundle\Manager;

/**
 * This interface defines contracts to get all defined configuration classes.
 */
interface SettingsRegistryInterface
{
    /**
     * Returns all settings classes, defined in the application.
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getSettingsClasses(): array;
}