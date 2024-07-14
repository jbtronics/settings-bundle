---
title: Caching settings data
layout: default
parent: Advanced
---

# Caching settings data

The settings-bundle has a built-in caching mechanism, which can be used to speed-up hydration of settings classes, in
certain scenarios.

## Principle

The caching mechanism hooks into the hydration process of settings classes. Normally settings are hydrated, by retrieving 
the settings data in a normalized form from the storage adapter. Afterwards, each property is converted to the target type
via the ParameterType classes. Also, it is checked, if any parameters are overwritten by an environment variable, then
the environment variable is converted to the correct type and applied.

The caching mechanism stores the parameters in the form, which there are used in the settings class. This means, that the
parameters can be directly moved into the settings class, and the loading from storage adapter, parameter type conversion
and environment variable overwriting can be skipped.

## Advantages

Therefore, caching can save some time especially in the following scenarios:

* Slow storage adapters, where the retrieval of the settings data is time-expensive (compared to the cache retrieval)
* Hydration into complex parameter types, where the conversion of normalized data to target type is time-expensive
* Scenarios where many environment variables are used to overwrite settings parameters, especially if the conversion from
  environment variable to target type is time-expensive

In other scenarios the caching mechanism, will most likely not have a significant impact on performance. At least for 
the writing scenarios, the caching mechanism can have a negative impact on performance, as the cache has to be filled with
a special data structure, along with the normal write access to the storage adapter.

## Limitations

The caching mechanism has some limitations and disadvantages however:

* Only types which can be serialized (with `serialize()`) can be cached. If you want to use complex data types in your
settings class, you might need to implement a custom serialization mechanism. If one property can not be serialzed, the 
whole settings class can not be cached.
* When cached settings are written back the storage adapter, additional overhead is introduced, as the cache has to be
updated too. In scenarios, where settings change very often, this might have a negative impact on performance, and caching
might be not very useful.
* The cache is not invalidated on changes of environment variables. That means, that if you change the value of an
environment variable, to overwrite a settings parameter value, the cache will still contain the old value. **Therefore, you
must clear the cache, if you change the value of an environment variable**, or it will not have any effect. You can do
this via the normal `console cache:clear` command.

## Usage

As caching might cause some overhead and exceptions could occur on classes, which can not be serialized, the caching is
disabled by default. To enable caching, a settings class has to be marked as cacheable.

You can do this explicitly via the `cacheable` attribute of the `Settings` attribute:

```php
#[Settings(cacheable: true)]
class TestSettings {
    use SettingsTrait;

    #[SettingsParameter()]
    public string $myString;
}
```

If no `cacheable` attribute is set, then the global value from the settings-bundle configuration is used
(`cache.default_cacheable` option), which is by default false. If you want to enable caching for all settings classes,
without specifying the `cacheable` attribute on all of them, you can set this option to true in the configuration.
You can then still disable caching for specific settings classes, by setting the `cacheable` option to false.

No further configuration is needed to use the caching mechanism. It happens transparently in the background, and you
should not notice any difference in the settings classes you get from the `SettingsManagerInterface`, or the way you
can use them.

By default, the cache is stored in the default cache pool of Symfony (`cache.app`). If you want to use another cache pool,
you can set the `cache.service` option in the settings-bundle configuration to the service id of the cache pool you want.

The cache have a default time-to-live (TTL) of 0, which means that the cache never expires, unless you clear it manually
(or the bundle detects a change to settings). If you want to set a different TTL, you can set the `cache.ttl` option in
the settings-bundle configuration to the number of seconds the cache should be valid. However normally this should not be
needed, as the cache is cleared automatically, if the settings change, and for performance reasons, you should avoid
invalidating the cache too often.