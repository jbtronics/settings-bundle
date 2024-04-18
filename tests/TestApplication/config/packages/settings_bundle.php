<?php

/**
 * Override any configuration for the bundle here
 */

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntry;
use Symfony\Config\JbtronicsSettingsConfig;

return static function (JbtronicsSettingsConfig $config)
{
    $config->defaultStorageAdapter(InMemoryStorageAdapter::class);
    $config->ormStorage()
        ->defaultEntityClass(SettingsEntry::class)
        ->prefetchAll(true)
    ;
};