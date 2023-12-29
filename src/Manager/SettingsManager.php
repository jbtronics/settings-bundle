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
        private readonly SettingsHydratorInterface $settingsHydrator
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

        //If not create a new instance of the given settings class with the default values
        $settings = $this->getNewInstance($settingsClass);
        $this->settingsHydrator->hydrate($settings, $this->schemaManager->getSchema($settingsClass));

        //Add it to our memory map
        $this->settings_by_class[$settingsClass] = $settings;
        return $settings;
    }

    public function reload(string|object $settings): object
    {
        if (is_string($settings)) {
            $settings = $this->get($settings);
        }
        //Reload the settings class from the storage adapter
        $this->settingsHydrator->hydrate($settings, $this->schemaManager->getSchema($settings));
    }

    public function save(string|object|null $settingsClass = null): void
    {
        if (is_object($settingsClass)) {
            $settingsClass = get_class($settingsClass);
        }

        /* If no settings class is given, save all settings classes
         * It is enough to only save the settings which are currently managed by the SettingsManager, as these are the
         * only ones which could have been changed.
         */
        $classesToSave = $settingsClass === null ? $this->settings_by_class : [$settingsClass];

        foreach ($classesToSave as $class) {
            $settings = $this->get($class);
            $this->settingsHydrator->persist($settings, $this->schemaManager->getSchema($class));
        }
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