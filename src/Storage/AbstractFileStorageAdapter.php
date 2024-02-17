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


namespace Jbtronics\SettingsBundle\Storage;

abstract class AbstractFileStorageAdapter implements StorageAdapterInterface
{

    protected array $cache = [];

    public function __construct(
        protected readonly string $storageDirectory,
        protected readonly string $defaultFilename
    )
    {
    }

    public function save(string $key, array $data, array $options = []): void
    {
        //Determine which filename to use for the given key
        $filename = $options['filename'] ?? $this->defaultFilename;
        
        //Save the content to the file
        $filePath = $this->getFilePath($filename);

        $this->cache[$filename][$key] = $data;

        //Write the content to the file
        $this->saveFileContent($filePath, $this->cache[$filename]);
    }

    public function load(string $key, array $options = []): ?array
    {
        //Determine which filename to use for the given key
        $filename = $options['filename'] ?? $this->defaultFilename;

        //Check if we already have the content of the file in the cache, then return it
        if (isset($this->cache[$filename]) && ($options['always_reload_file'] ?? false)) {
            return $this->cache[$filename][$key];
        }

        //Otherwise, try to load the content from the file
        $filePath = $this->getFilePath($filename);

        //Load the content from the file
        $this->cache[$filename] = $this->loadFileContent($filePath);

        //Return the key from the file
        return $this->cache[$filename][$key] ?? null;
    }

    /**
     * This method is called to unserialize the content of the file from the implemented file format into the normalized representation
     * Override this method to implement a different file loading mechanism
     * @param  string  $filePath
     * @return array|null
     */
    protected function loadFileContent(string $filePath): ?array
    {
        //If the file does not exist yet, return null
        if (!file_exists($filePath)) {
            return null;
        }

        //Load the content from the file
        $content = file_get_contents($filePath);

        //Unserialize the content into the normalized representation
        return $this->unserialize($content);
    }

    /**
     * Save the given data to the file with the given filename. Override this method to implement a different file saving mechanism
     * @param  string  $filePath
     * @param  array|null  $data
     * @return void
     */
    protected function saveFileContent(string $filePath, ?array $data): void
    {
        //Serialize the data into the implemented file format
        $content = $this->serialize($data);

        //Create the directory if it doesn't exist
        if (!is_dir(dirname($filePath))) {
            if (!mkdir($concurrentDirectory = dirname($filePath), 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        //Save the content to the file
        file_put_contents($filePath, $content);
    }

    /**
     * This method is called to unserialize the content of the file from the implemented file format into the normalized representation
     * @param  string  $content
     * @return array
     */
    abstract protected function unserialize(string $content): array;

    /**
     * This method is called to serialize the normalized representation of the data into the implemented file format
     * @param  array  $data
     * @return string
     */
    abstract protected function serialize(array $data): string;

    /**
     * Retrieves a safe filename for the given filename
     * @param  string  $filename
     * @return string
     */
    protected function getSafeFilename(string $filename): string
    {
        //Remove all characters that are not a-z, 0-9, ., _, or - from filename
        return preg_replace('/[^a-z0-9._\-]/i', '_', $filename);
    }

    /**
     * Retrieves the full path to the file with the given filename option
     * @param  string  $filename
     * @return string
     */
    protected function getFilePath(string $filename): string
    {
        return $this->storageDirectory . '/' . $this->getSafeFilename($filename);
    }
}