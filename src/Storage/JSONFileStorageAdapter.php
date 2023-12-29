<?php

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