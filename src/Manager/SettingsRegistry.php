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

use Jbtronics\SettingsBundle\Metadata\Driver\MetadataDriverInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This class is responsible for getting all configuration classes, defined in the application.
 * It scans the files in the defined directories for classes with the #[ConfigClass] attribute,
 * and also discovers classes registered via metadata drivers (e.g. YAML configuration).
 */
final class SettingsRegistry implements SettingsRegistryInterface
{

    private const CACHE_KEY = 'jbtronics.settings.settings_classes';

    /**
     * @param  MetadataDriverInterface  $metadataDriver The metadata driver to use for discovering additional settings classes
     * @param  CacheInterface  $cache The cache to use for caching the configuration classes
     * @param  bool  $debug_mode If true, the cache is ignored and the directories are scanned on every request
     * @param  array  $directories The directories to scan for configuration classes
     */
    public function __construct(
        private readonly MetadataDriverInterface $metadataDriver,
        private readonly CacheInterface $cache,
        private readonly bool $debug_mode
    )
    {
    }

    public function getSettingsClasses(): array
    {
        if ($this->debug_mode) {
            return $this->getSettingsClassUncached();
        }

        return $this->cache->get(self::CACHE_KEY, function () {
            return $this->getSettingsClassUncached();
        });
    }
    private function getSettingsClassUncached(): array
    {
        $classes = $this->metadataDriver->getAllManagedClassNames();

        $tmp = [];

        //Determine the short name for each class
        foreach ($classes as $class) {
            $name = $this->resolveSettingsName($class);

            if ($name === null) {
                continue;
            }

            //Ensure that the name is unique
            if (isset($tmp[$name])) {
                throw new \InvalidArgumentException(sprintf('There is already a class with the name %s (%s)!', $name, $tmp[$name]));
            }

            $tmp[$name] = $class;
        }

        return $tmp;
    }

    /**
     * Resolves the settings name for a given class, using either attributes or the metadata driver.
     * @return string|null The name, or null if the class is not a settings class
     */
    private function resolveSettingsName(string $class): ?string
    {
        $classMetadata = $this->metadataDriver->loadClassMetadata($class);
        if ($classMetadata !== null) {
            return $classMetadata->name ?? self::generateDefaultNameFromClassName($class);
        }

        return null;
    }

    /**
     * Generates a default name for the given class, based on the class name.
     * This is used, if no name is configured in the #[ConfigClass] attribute.
     * @param  \ReflectionClass|string  $class The class to generate the name for. Either given as classstring or as ReflectionClass
     * @phpstan-param \ReflectionClass|class-string $class
     * @return string
     */
    public static function generateDefaultNameFromClassName(\ReflectionClass|string $class): string
    {
        if (is_string($class)) {
            $reflectionClass = new \ReflectionClass($class);
        } else {
            $reflectionClass = $class;
        }

        $tmp = $reflectionClass->getShortName();
        //Remove the "Settings" suffix
        return strtolower(str_replace('Settings', '', $tmp));
    }

    public function getSettingsClassByName(string $name): string
    {
        $classes = $this->getSettingsClasses();
        if (!isset($classes[$name])) {
            throw new \InvalidArgumentException(sprintf('The settings class with the name "%s" does not exist!', $name));
        }

        return $classes[$name];
    }
}
