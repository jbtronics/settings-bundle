---
title: Storage adapters
layout: default
parent: Usage
nav_order: 3
---

# Storage adapters

The settings-bundle is designed to be agnostic of storage backends. That means that almost all functionality is implemented independently of a concrete storage backend. Therefore you can use all kind of different storage backends (files, database, etc.) and even implement your own storage backend without changing other parts of the bundle.

The concrete storage implementation is done via storage adapters, which must implement the `StorageAdapterInterface`. The bundle comes with a few default storage adapters, but you can also implement your own storage adapters.

The storage adapter, which a specific settings class uses, can be configured in the settings class annotation with the `storageAdapter` attribute. If no storage adapter is set there, the global default storage adapter is used, which can be configured with the `default_storage_adapter` key in the bundle configuration.

Some storage adapters may allow to pass further options to the adapter from the settings class annotation. These options are passed as an array to the `storageAdapterOptions` attribute in the settings class annotation.

## Built-in storage adapters

Following storage adapters are built-in:


### JSONFileStorageAdapter & PHPFileStorageAdapter

The `JSONFileStorageAdapter` and the `PHPFileStorageAdapter` save the settings data as JSON file respectively a PHP file. By default all settings are saved in a single file, whose name is determined by the `default_filename` key in the bundle configuration (plus the format extension). The file is created in the configured directory for settings files (`storage_directory` option in the bundle configuration).


If you want to save the data of certain settings classes in a separate file, you can pass the filename (including extension) as an option to the storage adapter. All settings classes with the same filename will be saved together in the same file.:
```php
#[Settings(storageAdapter: JSONStorageAdapter::class, storageAdapterOptions: [
    filename: 'my_settings.json'
])]
class MySettings
// ...
```

The PHPFileStorageAdapter loads the data directly into PHP, which is faster than parsing JSON. Be sure that the file contains only safe PHP code, as it will be executed directly by the PHP interpreter.

### ORMStorageAdapter

The `ORMStorageAdapter` stores the settings data in a database managed by doctrine ORM.The settings data is stored in 
instances of a SettingsEntry entity, which must extend the `Jbtronics\SettingsBundle\Entity\AbstractSettingsORMEntry` class.
The entity class to use can be either configured on a per-settings class basis (with the `entity_class` option), or globally for all settings classes (with the `default_entity_class` key under `orm_storage` in the bundle configuration).

The entity must extend the `AbstractSettingsORMEntry` class and must just define an ID field. All required fields are already defined by the abstract parent class:

```php
use Jbtronics\SettingsBundle\Entity\AbstractSettingsORMEntry;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
class MySettingsORMEntry extends AbstractSettingsORMEntry
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private int $id;
}
```

For performance reasons the ORMStorageAdapter performs a `SELECT *` query to load all settings data at once, to save a lot of single queries. 
This behavior can be disabled by setting the `prefetch_all` option under `orm_storage` to false in the bundle configuration.

### InMemoryStorageAdapter

Stores the settings in memory (RAM). The settings are not persisted and will be lost after the current request. This storage adapter is mainly used for testing.


## Creating custom storage adapters

You can create your own storage adapters by creating a new service implementing the `StorageAdapterInterface`. This way you can also create storage adapters for more complex storage backends, like databases, etc.

The storage adapter must implement a `load` method, which takes a key and returns the normalized settings data for this key, or null if no data is stored for this key. The `save` method takes a key and the normalized settings data and saves it. The options array from the settings class annotation is passed as the third argument to the `load` and `save` methods.

When you want to create a file based storage adapter, you can extend the `AbstractFileStorageAdapter` class, which already implements the `load` and `save` methods and just requires implementation of the format serialization/unserialization.