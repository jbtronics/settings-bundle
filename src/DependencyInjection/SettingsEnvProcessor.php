<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\DependencyInjection;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

/**
 * This env var processor allows to use settings in container parameters, via the syntax "%env(settings:settingsName:parameterName)%"
 * You should try to avoid using this processor, as it is not very performant. Instead, you should use the SettingsManager to retrieve the settings, whenever possible.
 */
final class SettingsEnvProcessor implements EnvVarProcessorInterface
{

    public function __construct(private readonly SettingsManagerInterface $settingsManager,
        private readonly MetadataManagerInterface $metadataManager)
    {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        //Split the name into the settings name (before :) and the parameter name
        $nameParts = explode(':', $name);
        if (count($nameParts) !== 2) {
            throw new EnvNotFoundException(sprintf('The environment variable "%s" is not valid. It must be in the format "settingsName:parameterName".', $name));
        }
        [$settingsName, $parameterName] = $nameParts;

        //Retrieve the settings instance
        $settings = $this->settingsManager->get($settingsName);

        //And resolve the parameter name
        $propertyName = $this->metadataManager->getSettingsMetadata($settingsName)->getParameter($parameterName)->getPropertyName();

        //Retrieve the parameter value via reflection
        return PropertyAccessHelper::getProperty($settings, $propertyName);
    }

    public static function getProvidedTypes(): array
    {
        return [
            'settings' => 'bool|int|float|string|array|' . \BackedEnum::class,
        ];
    }
}