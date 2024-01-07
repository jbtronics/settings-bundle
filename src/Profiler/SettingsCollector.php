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

namespace Jbtronics\SettingsBundle\Profiler;

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly SettingsRegistryInterface $configurationRegistry,
        private readonly SchemaManagerInterface $schemaManager,
        private readonly SettingsManagerInterface $settingsManager,
    )
    {

    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $settings_classes = $this->configurationRegistry->getSettingsClasses();
        $schemas = [];
        foreach ($settings_classes as $settings_class) {
            $schemas[$settings_class] = $this->schemaManager->getSchema($settings_class);
        }

        //Retrieve the settings objects from the settings manager
        $settings = [];
        foreach ($settings_classes as $settings_class) {
            $settings[$settings_class] = $this->settingsManager->get($settings_class);
        }

        $this->data = [
            'settings_classes' => $this->configurationRegistry->getSettingsClasses(),
            'schemas' => $schemas,
            'settings' => $settings,
        ];
    }

    public static function getTemplate(): ?string
    {
        return '@Settings/profiler/main.html.twig';
    }

    public function getSettingsClasses(): array
    {
        return $this->data['settings_classes'];
    }

    /**
     * @return array<string, SettingsSchema>
     */
    public function getSettingsSchemas(): array
    {
        return $this->data['schemas'];
    }

    /**
     * @template T of object
     * @param  string  $class
     * @phpstan-param class-string<T> $class
     * @return object
     * @phpstan-return T
     */
    public function getSettingsInstance(string $class): object
    {
        return $this->data['settings'][$class];
    }

    /**
     * Returns the value of the given parameter of the given settings class.
     * @param  string  $class
     * @param  string  $propertyName
     * @return mixed
     * @throws \ReflectionException
     */
    public function getSettingsParameterValue(string $class, string $propertyName): mixed
    {
        //Retrieve the private value via reflection
        $reflectionClass = new \ReflectionClass($class);
        return $reflectionClass->getProperty($propertyName)->getValue($this->data['settings'][$class]);
    }
}