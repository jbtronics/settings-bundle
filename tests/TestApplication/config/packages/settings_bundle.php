<?php

/**
 * Override any configuration for the bundle here
 */

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;

$container->loadFromExtension('jbtronics_settings', [
    'default_storage_adapter' => InMemoryStorageAdapter::class,

    'orm_storage' => [
        'default_entity_class' => \Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntryInterface::class,
        'fetch_all' => true,
    ],
]);