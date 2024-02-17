<?php

/**
 * Override any configuration for the bundle here
 */

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntry;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntryInterface;

$container->loadFromExtension('jbtronics_settings', [
    'default_storage_adapter' => InMemoryStorageAdapter::class,

    'orm_storage' => [
        'default_entity_class' => SettingsEntry::class,
        'prefetch_all' => true,
    ],
]);