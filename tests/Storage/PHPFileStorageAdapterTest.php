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

namespace Jbtronics\SettingsBundle\Tests\Storage;

use Jbtronics\SettingsBundle\Storage\JSONFileStorageAdapter;
use Jbtronics\SettingsBundle\Storage\PHPFileStorageAdapter;
use PHPUnit\Framework\TestCase;

class PHPFileStorageAdapterTest extends TestCase
{
    use FileAdapterTestTrait;

    private const STORAGE_DIR = __DIR__.'/../TestApplication/var/settings_test';

    private PHPFileStorageAdapter $service;

    protected function setUp(): void
    {
        //Remove the storage directory for a clean test
        $this->cleanStorageDir(self::STORAGE_DIR);
        $this->service = new PHPFileStorageAdapter(self::STORAGE_DIR, 'settings.php');
    }

    public function testSaveAndLoad(): void
    {
        $this->service->save('test', ['test' => 'value']);

        //Afterward a file should exist
        $this->assertFileExists(self::STORAGE_DIR.'/settings.php');
        $this->assertEquals(['test' => ['test' => 'value']], require self::STORAGE_DIR.'/settings.php');

        //We should be able to save files with different filenames
        $this->service->save('other_file', ['test' => 'value'], ['filename' => 'test.php']);
        $this->assertFileExists(self::STORAGE_DIR.'/test.php');

        //When we save more data, then it should be added to the existing file
        $this->service->save('test2', ['test' => 'value', 'test2' => 'value2']);
        $this->assertEquals([
            'test' => ['test' => 'value'],
            'test2' => ['test' => 'value', 'test2' => 'value2']
        ], require self::STORAGE_DIR.'/settings.php');

        //We should be able to load the data again
        $this->assertEquals(['test' => 'value'], $this->service->load('test'));
        $this->assertEquals(['test' => 'value', 'test2' => 'value2'], $this->service->load('test2'));

        //We should be able to load the data again with a different filename
        $this->assertEquals(['test' => 'value'], $this->service->load('other_file', ['filename' => 'test.php']));

        //This should also work fine with the always_reload_file option set
        $this->assertEquals(['test' => 'value'], $this->service->load('other_file', ['filename' => 'test.php' ,'always_reload_file' => true]));

        //If we request a key that does not exist, then it should return null
        $this->assertNull($this->service->load('does_not_exist'));

        //Or if we request a file that does not exist
        $this->assertNull($this->service->load('does_not_exist', ['filename' => 'does_not_exist.php']));
    }
}
