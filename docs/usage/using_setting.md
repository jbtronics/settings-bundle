---
title: Using settings
layout: default
parent: Usage
nav_order: 2
---

# Using settings

After you have defined the settings classes, you can use instances of them to save and store settings data.
The central service for retrieving and saving settings is the `SettingsManagerInterface` service.
All instances of settings instances must be retrieved from this service.

## Retrieving settings

To retrieve a settings instance, you can use the `get()` method of the `SettingsManagerInterface` service.
As the first argument, you must pass the class name or the short name of the settings class. You will then recieve the managed settings instance.
The settings instance is filled with the current settings data from the storage or with the default values if no data is available.

The settings manager keeps track of the settings instances internally and will return the same instance for the same settings class on subsequent calls to `get()`.

```php

$settings1 = $settingsManager->get(MySettings::class);

$settings2 = $settingsManager->get(MySettings::class);

//Both instances are exactly the same
var_dump($settings1 === $settings2); // true
```

By default the settings manager will directly load the data from storage and hydrate the settings instance. If you know, that you will not necessarily need the settings instance direcltly, you can pass `true` as the second argument (named `lazy`) to the `get()` method. If the settings instance were not loaded before, it will return a proxy object, that will load the settings instance on first access.

```php

//The settings instance is not loaded yet
$settings1 = $settingsManager->get(MySettings::class, true);

//As soon as we try to access a value, the settings instance is loaded
var_dump($settings1->getSomeValue()); // "some value"

```

## Save settings

You can modify the settings instances returned by the settings manager. As all references point to the same instance, all changes will be visible to all other parts of your application. However the changes to the settings instance are not persisted to the storage automatically. You have to call the `save()` method of the settings manager to persist the changes.

```php

$settings = $settingsManager->get(MySettings::class);
$settings->setSomeValue("new value");

//Persist the changes to this settings
$settingsManager->save($settings);

//And to all settings instances
$settingsManager->save();
```

The save method checks if the values of the settings instances are valid according to the settings class definition and their Validation attributes. If the settings instance is not valid, it will throw an exception and the changes are not persisted.

If `save()` is called without any arguments, it will persist all settings instances. If you pass a settings instance or a string with the class name as the first argument, only this settings instance will be persisted.

By default `save()` will also persist the embedded settings instances. If you want to persist only the top level settings instance, you can pass `false` as the second argument (named `cascade`).

## Reload settings

If you want to reset the changes done by your applications to the settings instance, you can call the `reload()` method of the settings manager. This will discard all changes and reload the settings instance from the storage.

```php

$settings = $settingsManager->get(MySettings::class);

$settings->setSomeValue("new value");

//Discard the changes
$settingsManager->reload($settings);

//The value is back to the original value
var_dump($settings->getSomeValue()); // "some value"
```

By default `reload()` will also reload the embedded settings instances. If you want to reload only the top level settings instance, you can pass `false` as the second argument (named `cascade`).

## Reset settings to their default values

If you want to reset the settings instance to the default values defined in the settings class code you can call the `resetToDefaultValues()` method 
of the settings manager. This will discard all changes and reload the settings instance from the default values. The value reset only affects the instance of the settings class and not the embedded settings instances. Also the changes are not persisted to the storage automatically (for this you need to call the `save()` method explicitly).

```php
$settings = $settingsManager->get(MySettings::class);

$settings->setSomeValue("new value");

//Reset the settings to their default values
$settingsManager->resetToDefaultValues($settings);

//Save the changes to the storage
$settingsManager->save($settings);
```