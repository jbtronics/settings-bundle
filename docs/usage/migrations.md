---
title: Settings versioning and migrations
layout: default
parent: Usage
---

# Settings versioning and migrations

Over time the structure and content of your settings classes will change. You will add new parameters, change names and types, remove parameters, etc. To handle this changes, the bundle comes with a versioning and migration system, which allows you to easily migrate existing stored settings data to the current version of your settings classes.

## Enabling versioning

By default versioning is disabled and it is assumed that the structure of your settings classes never change and the stored settings data always matches the current structure of your settings classes. To enable versioning, you have to set the `version` and `migrationService` option on a settings class.

```php
namespace App\Settings;

#[Settings(version: self::VERSION, migrationService: TestSettingsMigration::class)]
class TestSettings {
    public const VERSION = 2;
}
```

The `version` option defines the currently defined version of the settings class. The version is a simple integer (greater 0), which is increased everytime the structure of the settings class changes. The version is stored along side the settings data in the storage backend and is used to determine if the stored settings data need to be migrated to the current version of the settings class.

The `migrationService` option defines the class name of the service, which should be used to perform the migration. It will be called everytime the settings data need to be migrated to the current version of the settings class. This service must implement the `SettingsMigrationInterface` interface, however in many cases it will be easier to extend the `SettingsMigration` class, which defines some useful helpers to perform the migration.

The migrations are performed while hydrating an settings object, directly after the data was loaded from the storage backend and before the normalized data is applied to the object. This way the hydration of settings objects always happen with the required data format for the current version of the settings class.
To avoid doing the migration everytime the settings object is hydrated, the migrated data is stored back to the storage backend, after migration and successful hydration. This way the migration is only performed once for each settings object.

## Creating migrations

The migrator is a service implementing the `SettingsMigrationInterface` interface. It is called everytime the settings data need to be migrated to a newer version of the settings class. 

The SettingsMigrationInterface defines a `migrate` method, which takes the old stored data, the old version and the new target version. The method perfoms the desired migration between this too versions and returns an array containing the new data, which will be used further in the settings bundle.

### Extending SettingsMigration

Instead of directly implementing the SettingsMigrationInterface directly, its easier to extend the `SettingsMigration` class, which defines useful helpers for stepwise migrations.

Everytime you increment the version of your settings class, you have to implement a new method `migrateToVersionN` in your migration class, where N is the new version of your settings class. This method will be called to migrate the settings data from version N-1 to version N. The method takes the old data and the metadata of the settings class as parameters and returns the new data. 

```php

namespace App\Settings\Migrations;

use Jbtronics\SettingsBundle\Migrations\SettingsMigration;

class TestSettingsMigration extends SettingsMigration  {
    
    /**
     * This method is called automatically by the migration class and handles 
     * migration of version 0 (non versioned settings) to version 1.
     */
    public function migrateToVersion1(array $data, SettingsMetadata $metadata): array
    {
        /*
        * Change the data here however you want to match the new settings schema
        * The key name is the parameter name (not necessarily the property name)
        * The value is the normalized value of the parameter 
        */

        $data['newValue'] = $data['oldInt'] + 1;

        //You can also unset old parameters, if you want to remove them
        unset($data['oldInt']);

        return $data;
    }

    /**
     * This method is called, to handle migration from version 1 to version 2.
     */
    public function migrateToVersion2(array $data, SettingsMetadata $metadata): array
    {
        //Perform some more migrations...

        return $data;
    }

}
```

### Changing which methods are called

By default the `migrateToVersionN()` methods of the migrator are found and called automatically if they exist. If you wanna define this behavior explicitly or override the logic which handlers are called, you can override the `resolveStepHandler()` method of the base class and return a Closure for your desired handler:

```php

class TestSettingsMigration extends SettingsMigration {

    //...

    public function mySpecialMigrator(array $data, SettingsMetadata $metadata): array
    {
        //Perform some special migration logic here

        return $data;
    }

    protected function resolveStepHandler(int $version): \Closure
    {
        return match($version) {
            5 => $this->mySpecialMigrator(...),
            default => parent::resolveStepHandler($version),
        };
    }
}

```