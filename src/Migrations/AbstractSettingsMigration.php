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

use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;

/**
 * Extend this class for easier migration implementation.
 * This class allows for easy step-by-step migration of settings data, which is often more convenient than implementing
 * the SettingsMigrationInterface directly.
 *
 * The class tries to call the method migrateToVersionN($data, $metadata) for each version step, where N is the version
 * number. If the method does not exist, an exception is thrown. You can override the resolveStepHandler() method to
 * change this behavior.
 */
class AbstractSettingsMigration implements SettingsMigrationInterface
{

    public function migrate(SettingsMetadata $metadata, array $data, int $oldVersion, int $newVersion): array
    {
        //Iterate step-by-step through all versions
        for ($version = $oldVersion; $version <= $newVersion; $version++) {
            $stepHandler = $this->resolveStepHandler($version);
            $data = $stepHandler($data, $metadata);
        }

        return $data;
    }

    /**
     * This function resolves
     * @param  int  $version
     * @return \Closure
     */
    protected function resolveStepHandler(int $version): \Closure
    {
        //Try to find the step handler method for in this class (or its children) with the Syntax migrateToVersionN()
        $method = 'migrateToVersion' . $version;
        if (method_exists($this, $method)) {
            return $this->$method(...);
        }

        //If no step handler method was found, throw an exception
        throw new \LogicException('No step handler method found for migration to version ' . $version . '. Please implement the method ' . $method . ' in your migration class or override the resolveStepHandler() method for explicit configuration.');
    }
}