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

namespace Jbtronics\SettingsBundle\Storage;

class JSONFileStorageAdapter implements StorageAdapterInterface
{

    public function __construct(
        private readonly string $storageDirectory
    )
    {

    }

    private function getFileName(string $key): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);

        return $this->storageDirectory . '/' . $filename . '.json';
    }

    public function save(string $key, array $data): void
    {
        //Convert the key to a safe filename
        $filename = $this->getFileName($key);

        //Create the directory if it doesn't exist
        if (!is_dir(dirname($filename))) {
            if (!mkdir($concurrentDirectory = dirname($filename), 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        //Save the data to the file
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @throws \JsonException
     */
    public function load(string $key): ?array
    {
        //Convert the key to a safe filename
        $filename = $this->getFileName($key);

        //Check if the file exists
        if (!file_exists($filename)) {
            return null;
        }

        //Load the data from the file
        $data = file_get_contents($filename);

        //Decode the data
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}