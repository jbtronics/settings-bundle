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


namespace Jbtronics\SettingsBundle\Migrations;

use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsResetterInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;

final class EnvVarToSettingsMigrator implements EnvVarToSettingsMigratorInterface
{
    public function __construct(
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsManagerInterface $settingsManager,
        private readonly SettingsResetterInterface $settingsResetter,
        private readonly SettingsHydratorInterface $envVarHydrator,
    ) {
    }

    public function migrate(SettingsMetadata|string $settingsClass): void
    {
        if (is_string($settingsClass)) {
            $settingsMetadata = $this->metadataManager->getSettingsMetadata($settingsClass);
        } else {
            $settingsMetadata = $settingsClass;
        }

        //Load the currently stored instance of the settings
        $settings = $this->settingsResetter->newInstance($settingsMetadata);
        $settings = $this->envVarHydrator->hydrate($settings, $settingsMetadata, true);

        //The settings instance already contains the values from the environment variables.
        //Now we just need to save the settings instance to the database
        $this->envVarHydrator->persist($settings, $settingsMetadata);
    }

    public function getAffectedEnvVars(SettingsMetadata|string $settingsMetadata): array
    {
        if (is_string($settingsMetadata)) {
            $settingsMetadata = $this->metadataManager->getSettingsMetadata($settingsMetadata);
        }

        $result = [];

        //Iterate over each settings parameter and check if it is overwritten by an environment variable
        foreach ($settingsMetadata->getParameters() as $parameter) {
            if (!$this->settingsManager->isEnvVarOverwritten($settingsMetadata->getClassName(), $parameter))
            {
                continue;
            }

            $result[] = $parameter->getBaseEnvVar();
        }

        return $result;
    }
}