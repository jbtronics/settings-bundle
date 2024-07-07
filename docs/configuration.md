---
layout: default
title: Configuration
nav_order: 5
---

# Configuration

Most of the behavior of the settings-bundle are configured directly in the settings classes with attributes. However there are a few things, which can be configured globally. All configuration is done in a file with a `jbtronics_settings` key in the `config/packages` directory (e.g. `config/packages/jbtronics_settings.yaml`).

## Configuration reference

The following is the configuration reference for the settings-bundle with their default values:

```yaml
# config/packages/jbtronics_settings.yaml

jbtronics_settings:

    # The folders where settings classes should be searched for
    search_paths:
        - '%kernel.project_dir%/src/Settings'

    # The class name of the service, which is used on all storage adapters if
    # non is set explicitly. Can be null, if the storage adapter is configured # explicitly everywhere
    default_storage_adapter: ~

    # If this is set to true, the settings-bundle will automatically save the
    # migrated data back to the storage adapter after migration was successful
    # This improves performance, as the data is only saved once.
    # Set to false, if you want to explicitly save the data after migration
    save_after_migration: true

    # Proxy configuration

    # The namespace of the proxy classes
    proxy_namespace: 'Jbtronics\SettingsBundle\Proxies'

    # The directory where the proxy classes should be stored
    proxy_dir: '%kernel.cache_dir%/jbtronics_settings/proxies'

    # The configuration for caching of settings
    cache:
        # The service id of the cache pool in which the settings should be cached
        service: 'cache.app'
        
        # The default value for all classes, where the cacheable option is not explictly set
        # True means, that the settings are cacheable and will be cached if possible
        default_cacheable: false

    # The configuration for file based storage adapters
    file_storage:
        # The directory where the settings files should be stored
        storage_directory: '%kernel.project_dir%/var/jbtronics_settings/'
        
        # The default filenmame (without extension) in which the settings are stored under in the storage directory
        # The file extension is determined by the storage adapter. The name can be overriden on a per settings class basis
        default_filename: 'settings'
        
    # The configuration for the ORM storage adapter
    orm_storage:
      # The default entity (extending AbstractSettingsORMEntry) to use to store the settings data. If not set, the entity class must be set in the settings class annotation
      default_entity_class: ~

      # If this is set to true, the ORM storage adapter will perform a SELECT * query on the settings table to load all settings at once, instead of fetching them one by one.
      # This can improve performance, if you have a lot of settings, which are loaded frequently. 
      # However, it can also decrease performance, if you have a lot of settings classes, which are loaded rarely.
      prefetch_all: true
    

```