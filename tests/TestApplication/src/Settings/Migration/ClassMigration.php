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


namespace Jbtronics\SettingsBundle\Tests\TestApplication\Settings\Migration;

use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Migrations\SettingsMigration;
use Jbtronics\SettingsBundle\Tests\TestApplication\Helpers\TestEnum;

/**
 * This class is used to migrate the settings data of the VersionedSettings class.
 */
class ClassMigration extends SettingsMigration
{
    public function migrateToVersion1(array $data, SettingsMetadata $metadata): array
    {
        $data['migrated'] = true;

        return $data;
    }

    public function migrateToVersion2(array $data, SettingsMetadata $metadata): array
    {
        //Use the PHPValueConverterTrait to retrieve a bool value from the settings data
        $migrated = $this->getAsPHPValue('migrated', $data, $metadata);

        //Simulate some migration logic
        if ($migrated) {
            $this->setAsPHPValue('enum', TestEnum::BAR, $data, $metadata);
        }

        return $data;
    }
}