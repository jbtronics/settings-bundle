<?php


/*
 * Copyright (c) 2024 Jan Böhmer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Exception\SettingsNotValidException;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;

/**
 * This service manages all available settings classes and keeps track of them
 */
final class SettingsManager implements SettingsManagerInterface
{
    public function __construct(
        private readonly SchemaManagerInterface $schemaManager,
        private readonly SettingsHydratorInterface $settingsHydrator,
        private readonly SettingsResetterInterface $settingsResetter,
        private readonly SettingsValidatorInterface $settingsValidator,
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

        //Reset the settings class to its default values
        $this->resetToDefaultValues($settings);

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

        $errors = [];

        //Ensure that the classes are all valid
        foreach ($classesToSave as $class) {
            $settings = $this->get($class);

            $errors_per_property = $this->settingsValidator->validate($settings);
            if (count($errors_per_property) > 0) {
                $errors[$class] = $errors_per_property;
            }
        }

        //If there are any errors, throw an exception
        if (count($errors) > 0) {
            throw new SettingsNotValidException($errors);
        }

        //Otherwise persist the settings
        foreach ($classesToSave as $class) {
            $settings = $this->get($class);

            $this->settingsHydrator->persist($settings, $this->schemaManager->getSchema($class));
        }
    }

    public function resetToDefaultValues(object|string $settings): void
    {
        if (is_string($settings)) {
            $settings = $this->get($settings);
        }

        //Reset the settings class to its default values
        $this->settingsResetter->resetSettings($settings, $this->schemaManager->getSchema($settings));
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

        //If the class is resettable, we call the reset method
        if (is_a($settingsClass, ResettableSettingsInterface::class)) {
            $instance->resetToDefaultValues();
        }

        return $instance;
    }
}