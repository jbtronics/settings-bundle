<?php

/**
 * Override any configuration for the bundle here
 */

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntry;

return [
    'jbtronics_settings' => [
        'default_storage_adapter' => InMemoryStorageAdapter::class,
        'orm_storage' => [
            'default_entity_class' => SettingsEntry::class,
            'prefetch_all' => true,
        ],
        'yaml_mapping_paths' => [
            __DIR__ . '/../yaml_mapping'
        ],
    ],
];
