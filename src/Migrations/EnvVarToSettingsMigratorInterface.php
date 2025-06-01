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

use Jbtronics\SettingsBundle\Exception\SettingsNotValidException;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;

/**
 * This service can be used to migrate the current environment variables to a settings entry.
 * environment variables will be resolved to settings objects, and these values will be stored in the storage, no matter
 * of the envVarMode.
 * This way legacy environment variables can be migrated to settings entries, and the env variables can be removed
 * afterward.
 */
interface EnvVarToSettingsMigratorInterface
{
    /**
     * Converts the environment variables of the given settings metadata to settings entries.
     * The settings will be written to the storage, including all environment variables, that would
     * normally not be saved due to the envVarMode.
     * It loads its own instance directly from storage, so this behaves not like a normal settings save.
     * If the $check_validity is set to true, it will check if the settings metadata is valid, and throw an exception
     * if not valid.
     * @param  SettingsMetadata|string  $settingsClass
     * @param bool $check_validity
     * @throws SettingsNotValidException If $check_validity is true and the settings metadata is not valid.
     * @return void
     */
    public function migrate(SettingsMetadata|string $settingsClass, bool $check_validity = true): void;

    /**
     * Returns a list of environment variables that were/will be affected by the migration of the given settings metadata.
     * This is intended to be used as a hint for the user which environment variables he can safely remove after the
     * migration. It should not be used for anything else.
     * @param  SettingsMetadata|string  $settingsMetadata
     * @return string[]
     */
    public function getAffectedEnvVars(SettingsMetadata|string $settingsMetadata): array;
}