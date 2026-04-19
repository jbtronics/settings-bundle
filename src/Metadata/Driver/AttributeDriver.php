<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 * Copyright (c) 2026 Sviatoslav Vysitskyi
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

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Metadata\Driver;

use Ergebnis\Classy\Construct;
use Ergebnis\Classy\Constructs;
use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

/**
 * Metadata driver that reads settings metadata from PHP 8.1+ attributes.
 * This is the default driver and provides backwards compatibility with the existing attribute-based configuration.
 */
final class AttributeDriver implements MetadataDriverInterface
{
    /**
     * @param  string[]  $searchPathes Directories to search for settings classes (e.g. src/Settings/)
     */
    public function __construct(
        private readonly array $searchPathes,
    ) {

    }

    public function isSettingsClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        return count($attributes) > 0;
    }

    public function loadClassMetadata(string $className): ?Settings
    {
        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        if (count($attributes) < 1) {
            return null;
        }
        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The class "%s" has more than one Settings attributes! Only one is allowed', $className));
        }

        return $attributes[0]->newInstance();
    }

    public function loadParameterMetadata(string $className): array
    {
        $parameters = [];
        $reflProperties = PropertyAccessHelper::getProperties($className);

        foreach ($reflProperties as $reflProperty) {
            $attributes = $reflProperty->getAttributes(SettingsParameter::class);
            if (count($attributes) < 1) {
                continue;
            }
            if (count($attributes) > 1) {
                throw new \LogicException(sprintf(
                    'The property "%s" of the class "%s" has more than one SettingsParameter attributes! Only one is allowed',
                    $reflProperty->getName(),
                    $className
                ));
            }

            $parameters[$reflProperty->getName()] = $attributes[0]->newInstance();
        }

        return $parameters;
    }

    public function loadEmbeddedMetadata(string $className): array
    {
        $embeddeds = [];
        $reflProperties = PropertyAccessHelper::getProperties($className);

        foreach ($reflProperties as $reflProperty) {
            $attributes = $reflProperty->getAttributes(EmbeddedSettings::class);
            if (count($attributes) < 1) {
                continue;
            }
            if (count($attributes) > 1) {
                throw new \LogicException(sprintf(
                    'The property "%s" of the class "%s" has more than one EmbeddedSettings attributes! Only one is allowed',
                    $reflProperty->getName(),
                    $className
                ));
            }

            $embeddeds[$reflProperty->getName()] = $attributes[0]->newInstance();
        }

        return $embeddeds;
    }

    public function getAllManagedClassNames(): array
    {
        $pathes = $this->searchPathes;
        $classes = [];

        foreach ($pathes as $path) {
            //Skip non-existing directories
            if (!is_dir($path)) {
                continue;
            }

            //Find all PHP classes in the given directories
            $constructs = Constructs::fromDirectory($path);
            $names = array_map(static function (Construct $construct): string {
                return $construct->name();
            }, $constructs);

            $classes = array_merge($classes, $names);
        }

        //Now filter out all classes, which donot have the #[Settings] attribute
        $settings_classes = [];

        foreach ($classes as $class) {
            $reflClass = new \ReflectionClass($class);
            $attributes = $reflClass->getAttributes(Settings::class);
            if (count($attributes) > 0) {
                $settings_classes[] = $class;
            }
        }

        return $settings_classes;
    }
}
