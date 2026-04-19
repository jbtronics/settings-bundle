---
title: YAML Configuration
layout: default
parent: Usage
nav_order: 2
---

# YAML Configuration

{: .important }
> It is recommended to use PHP attributes for settings configuration. This YAML configuration option is currently more considered experimentally, and might change in the future or be removed completely in future versions.

By default, settings classes are configured using PHP attributes (`#[Settings]`, `#[SettingsParameter]`, etc.). As an alternative, you can define settings metadata in YAML files. This allows you to keep your settings classes as plain PHP in the application layer, while the infrastructure-level configuration (storage adapters, parameter types, labels, etc.) lives in YAML files — following the same pattern as Doctrine ORM's YAML mapping.

## Requirements

Install the `symfony/yaml` package:

```bash
composer require symfony/yaml
```

## Setup

Configure the directories where your YAML mapping files are located in your bundle configuration:

```yaml
# config/packages/jbtronics_settings.yaml

jbtronics_settings:
    yaml_mapping_paths:
        - '%kernel.project_dir%/config/settings'
```

## Defining settings in YAML

### Step 1: Create a plain PHP settings class

Your settings class is a regular PHP class with typed properties. No attributes needed.

```php
<?php
// src/Settings/AppSettings.php

namespace App\Settings;

class AppSettings
{
    public string $siteName = 'My Application';

    public ?int $itemsPerPage = 25;

    public bool $maintenanceMode = false;
}
```

### Step 2: Create a YAML mapping file

Create a YAML file in your configured mapping directory. The file name should follow the convention of replacing namespace backslashes with dots:

`App\Settings\AppSettings` → `App.Settings.AppSettings.yaml`

```yaml
# config/settings/App.Settings.AppSettings.yaml

App\Settings\AppSettings:
    name: app
    storageAdapter: Jbtronics\SettingsBundle\Storage\JSONFileStorageAdapter
    label: "Application Settings"
    description: "General application configuration"

    parameters:
        siteName:
            type: Jbtronics\SettingsBundle\ParameterTypes\StringType
            label: "Site Name"
            description: "The name of the application"

        itemsPerPage:
            type: Jbtronics\SettingsBundle\ParameterTypes\IntType
            label: "Items per Page"
            nullable: true

        maintenanceMode:
            type: Jbtronics\SettingsBundle\ParameterTypes\BoolType
            label: "Maintenance Mode"
            groups:
                - admin
```

## YAML reference

### Class-level options

The top-level key is the fully qualified class name. All options mirror the `#[Settings]` attribute:

| Option | Type | Default | Description |
|---|---|---|---|
| `name` | string | auto-generated | Short name for the settings class |
| `storageAdapter` | string | global default | FQCN of the storage adapter service |
| `storageAdapterOptions` | array | `[]` | Options passed to the storage adapter |
| `groups` | string[] | `null` | Default groups for parameters |
| `version` | int | `null` | Version number for migrations |
| `migrationService` | string | `null` | FQCN of the migration service |
| `dependencyInjectable` | bool | `true` | Whether the class can be injected via DI |
| `label` | string | `null` | User-friendly label |
| `description` | string | `null` | User-friendly description |
| `cacheable` | bool | `null` | Override caching behavior |

### Parameter options (under `parameters:`)

Each key under `parameters` is a property name. Options mirror the `#[SettingsParameter]` attribute:

| Option | Type | Default | Description |
|---|---|---|---|
| `type` | string | auto-guessed | FQCN of the parameter type |
| `name` | string | property name | Internal name for the parameter |
| `label` | string | `null` | User-friendly label |
| `description` | string | `null` | User-friendly description |
| `options` | array | `[]` | Extra options for the parameter type |
| `formType` | string | `null` | FQCN of the Symfony form type |
| `formOptions` | array | `[]` | Options passed to the form type |
| `nullable` | bool | auto-detected | Whether the value can be null |
| `groups` | string[] | `null` | Groups this parameter belongs to |
| `envVar` | string | `null` | Environment variable name |
| `envVarMode` | string | `INITIAL` | One of: `INITIAL`, `OVERWRITE`, `OVERWRITE_PERSIST` |
| `envVarMapper` | string | `null` | FQCN of a ParameterType service for env var mapping |
| `cloneable` | bool | `true` | Whether property is cloned when settings are cloned |

### Embedded settings options (under `embeddedSettings:`)

Each key under `embeddedSettings` is a property name. Options mirror the `#[EmbeddedSettings]` attribute:

| Option | Type | Default | Description |
|---|---|---|---|
| `target` | string | auto-detected | FQCN of the embedded settings class |
| `groups` | string[] | `null` | Groups this embedded class belongs to |
| `label` | string | `null` | User-friendly label |
| `description` | string | `null` | User-friendly description |
| `formOptions` | array | `null` | Options passed to the embedded form |

## Embedded settings example

```yaml
# config/settings/App.Settings.DashboardSettings.yaml

App\Settings\DashboardSettings:
    storageAdapter: Jbtronics\SettingsBundle\Storage\JSONFileStorageAdapter

    parameters:
        title:
            type: Jbtronics\SettingsBundle\ParameterTypes\StringType
            label: "Dashboard Title"

    embeddedSettings:
        widgetSettings:
            target: App\Settings\WidgetSettings
            label: "Widget Configuration"
            groups:
                - admin
```

## Mixing attributes and YAML

You can use both attributes and YAML in the same project. Each settings class should use one or the other — if a class has `#[Settings]` attributes, those take precedence over any YAML configuration for that class.

## Limitations

- **Callable `envVarMapper`**: YAML only supports class-string references (FQCN of a `ParameterTypeInterface` service). PHP closure mappers are not available in YAML — use attributes if you need callable mappers.
- **`TranslatableInterface` labels**: YAML labels and descriptions are plain strings. Use translation keys as strings for i18n support, which is the standard Symfony approach.
- **Type guessing**: Works the same as with attributes — if `type` is omitted, the bundle guesses from the PHP property type declaration.
