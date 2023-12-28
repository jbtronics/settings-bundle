<?php

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;

/**
 * This service manages all available settings classes and keeps track of them
 */
final class SettingsManager implements SettingsManagerInterface
{
    public function __construct(
        private readonly SchemaManagerInterface $schemaManager,
        private readonly SettingsRegistryInterface $settingsRegistry,
    )
    {

    }

    private array $settings_by_class = [];

    public function get(string $settingsClass): object
    {
        //Check if the settings class is already in memory
        if (isset($this->settings_by_class[$settingsClass])) {
            return $this->settings_by_class[$settingsClass];
        }

        //Ensure that the given class is a settings class
        if (!$this->schemaManager->isSettingsClass($settingsClass)) {
            throw new \LogicException(sprintf('The class "%s" is not a settings class. Add the #[Settings] attribute to the class.', $settingsClass));
        }

        //If not, load it
        $settings = $this->reload($settingsClass);

        //Add it to our memory map
        $this->settings_by_class[$settingsClass] = $settings;
        return $settings;
    }

    public function reload(?string $settingsClass): object
    {
        //TODO: Retrieve the settings class from the storage provider

        //For now we just return a new instance
        return $this->getNewInstance($settingsClass);
    }

    public function save(?string $settingsClass = null): void
    {
        // TODO: Implement save() method.
    }

    private function loadSettings(string $settingsClass): object
    {
        //F
    }

    /**
     * Creates a new instance of the given settings class.
     * @param  string  $settingsClass
     * @return object
     */
    private function getNewInstance(string $settingsClass): object
    {
        $reflectionClass = new \ReflectionClass($settingsClass);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        //TODO: Add logic to initialize the instance

        return $instance;
    }
}