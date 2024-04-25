---
title: Using settings
layout: default
parent: Usage
nav_order: 2
---

# Using settings

After you have defined the settings classes, you can use instances of them to save and store settings data.
The central service for retrieving and saving settings is the `SettingsManagerInterface` service.
All instances of settings instances must be retrieved from this service or via dependency injection.

## Retrieving settings via the settings manager

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

By default, the settings manager will directly load the data from storage and hydrate the settings instance. If you know, that you will not necessarily need the settings instance direcltly, you can pass `true` as the second argument (named `lazy`) to the `get()` method. If the settings instance were not loaded before, it will return a proxy object, that will load the settings instance on first access.

```php

//The settings instance is not loaded yet
$settings1 = $settingsManager->get(MySettings::class, true);

//As soon as we try to access a value, the settings instance is loaded
var_dump($settings1->getSomeValue()); // "some value"

```

## Retrieving settings via dependency injection

If the settings class is marked as `dependencyInjectable`, which is the default, you can also inject the settings instance
via dependency injection. The settings bundle registers a service for each settings class, which can be injected into your services
like any other service. Internally the settings manager `get()` method is called to retrieve a lazy loaded settings instance.

If symfony autowiring is enabled, you just need to typehint the settings class in the constructor of your service.

```php

use Jbtronics\SettingsBundle\Settings\MySettings;

class MyService
{
    public function __construct(private MySettings $settings) {
        //The $settings instance can be used like normal and always contains the current settings data
    }
}
```

This dependency injection pattern makes services easily testable, as you can replace the settings instance with
a mock object in your tests and have no additional dependencies to the settings manager.

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

### Retrieving temporary settings copies

One downside that the settings instances provided by the SettingsManager are shared between all parts of your application, is that changes immediately affect all parts of your application. If you set a parameter to an invalid parameter, it might break other parts of your application, which rely on the settings instance.

To avoid this, you can retrieve a temporary copy of the settings instance, which is not shared with other parts of your application. You can do this by calling the `createTemporaryCopy()` method of the settings manager. This will return a independent copy of the settings instance, whose values can be modified without affecting other parts of your application. The temporary contains all the values of the original settings instance, but everything is a new instance. This also affects embedded settings instances, which are also replaced by a temporary copy.

To apply the modifications on the temporary copy back to the original settings instance, you can call the `mergeTemporaryCopy()` method of the settings manager. The parameter data managed by the temporary copy will overwrite the data of the original settings instance. So be sure that the data of the managed settings were not changed in the meantime (or these changes get lost).

The second parameter of the `mergeTemporaryCopy()` method is a boolean, which determines if the temporary copy should be merged recursively. If set to `true` (which is the default), all settings embedded in the temporary copy will also be merged into the original settings instance. If set to `false`, only the top level settings will be merged.

The `mergeTemporaryCopy()` will also validate the data of the temporary copy according to the settings constraints. If the data is not valid, it will throw an exception and the merge is not performed. This ensures, that only valid data is merged back into the original settings instance and you can not accidentally break the application.

Temporary copies are especially useful, if you work with forms, where users can modify the data and invalid parameter values could break the applications. In that case you can create a temporary copy of the settings instance, bind the form to the temporary copy and only if the form is valid, merge the temporary copy back into the original settings instance. See the forms documentation for more information.

```php

$settings = $settingsManager->get(MySettings::class);

$settings->setSomeValue("new value");

//Create a temporary copy of the settings instance
$temporarySettings = $settingsManager->createTemporaryCopy($settings);
//We can modify the temporary copy without affecting the original settings instance
$temporarySettings->setSomeValue("another value");
var_dump($settings->getSomeValue()); // "new value"

//Until we merge the temporary copy back into the original settings instance
$settingsManager->mergeTemporaryCopy($temporarySettings);

//The original settings instance now contains the new value
//And it is ensured that the data is also vaild according to the validator constraints
var_dump($settings->getSomeValue()); // "another value"

//Persist the changes to the storage
$settingsManager->save($settings);
```

### Customizing the clone and merge behavior

By default, all objects in the settings parameters are cloned during the creation and merging of temporary copies to get a truly independent copy of the settings instance. If an object is not cloneable, you can specifiy the `cloneable: false` option on the settings parameter attribute to disable cloning for this parameter. In this case the object will be copied by reference. You can customize the cloning behavior like described below:

```php
//This object does not get cloned, but passed by reference to the temporary copy
#[SettingsParameter(cloneable: false)]
private NonCloneableObject $nonCloneableObject;
```

If you want to customize the cloning and merging behavior even more you can implement the `CloneAndMergeAwareSettingsInterface` in your settings class. This interface provides an `afterSettingsClone()` method, which is called on the *clone* after the default cloning logic is applied. This method can be used to customize the cloning behavior of the settings instance. The `afterSettingsMerge()` method is called on the original settings instance after the default merging logic is applied. This method can be used to customize the merging behavior of the settings instance.

By default, only settings parameters get cloned and merged by the bundle and all other properties keep their default or old values (as the cloner creates a new instance of the class). If you want to copy over additional properties, you can do this in the `afterSettingsClone()` and `afterSettingsMerge()` methods.

```php
use Jbtronics\SettingsBundle\Settings\CloneAndMergeAwareSettingsInterface;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\Settings\Settings;

class MySettings extends Settings implements CloneAndMergeAwareSettingsInterface
{
    #[SettingsParameter]
    private string $someValue = "some value";

    private string $additionalValue = "additional value";

    public function afterSettingsClone(self $original): void
    {
        $this->additionalValue = $original->additionalValue;
    }

    public function afterSettingsMerge(self $clone): void
    {
        $this->additionalValue = $clone->additionalValue;
    }

    //...

}
```