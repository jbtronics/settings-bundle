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

```