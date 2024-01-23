---
title: Using settings as container parameters
layout: default
parent: Advanced
---

# Using settings as container parameters

{: .warning }
> You should try to avoid using settings as container parameters, whereever possible, as it can harm performance and can lead to weird effects. This is only a workaround for cases, where you can not avoid it, normally you should use the `SettingsManagerInterface` service to access settings.

Symfony has the concept of container parameters used to configure the application and its services. The user changable settings managed by this bundle should normally retrieved via the `SettingsManagerInterface` service. However in some cases (e.g. working with third-party code you can not change) you wanna use the values configured in a settings class, passed directly to a service similar to a classical container parameter. For this purpose this bundle offers a special Environment Variable Processor. 

Normally symfonys container parameters are pretty much static, as they are only evaluated once at container compilation time. The exception from this rule, are parameters derived from environment variables (`%env(ENV_VARIABLE)%` syntax). These parameters are evaluated at runtime, whenever the services are initialized. Symfony offers the concept of [Environment Variable Processors](https://symfony.com/doc/current/configuration/env_var_processors.html), to slightly filter or modify the value of a environment variable before it is passed to a service.

This bundle "abuses" these processors, to implement a possibility to use settings as container parameters. Instead of resolving a environment variable and modifying it value, it loads the specified settings value.

## Usage

The basic syntax to refer to a settings value as container parameter is `%env(settings:settingsName:parameterName)`. It should be possible to use this syntax everywhere, where you can use a container parameter (config files, Autowire attribute, etc.).

The `settingsName` is the short name of the settings class (e.g. `test` for `App\Settings\TestSettings` unless explicitly specified). The `parameterName` is the name of the parameter inside the settings class (not necessarily the property name!).

If you have a settings class like this:

```php

#[Settings]
class TestSettings {
    use SettingsTrait;

    #[SettingsParameter()]
    public string $myString;
}
```

You can use the settings value in your config files like this:

```yaml
testConfig:
    myValue: '%env(settings:test:myString)%'
```

It should be posssible to combine this with other environment variable processors, e.g. invert a boolean value, etc.

## Limitations

The Environment Variable Processor is only able to load the settings value, but not to convert it to the desired type. Therefore you have to make sure, that the settings value is of the correct type.


Symfony can only work with `string`, `int`, `float`, `bool`, `array` or `\BackedEnum` values as env variable types. You can not inject an settings parameter of any other type (e.g. `\DateTime` or `\DateTimeImmutable`). If you need to use a more complex type, you have to use the `SettingsManagerInterface` service to retrieve the settings value and convert it to the desired type.


Symfony only allows alphanumeric and underscore characters in the env variable name. Therefore you can not refer to a parameter or settings calss with a name containing other characters.

For performance reasons the settings parameter value is directly accessed via reflection, not via eventual getter/setter methods. Therefore the getter/setter methods are not called, when accessing a parameter this way.

The settings value is only resolved once the container or the service refering to the parameter is initialized. If the settings value changes, later for example via the `SettingsManagerInterface`, the container or service will still use the old value. Therefore you should only use this feature for settings, which are not changed during runtime. If you have a symfony runtime, which reuses the container for multiple requests, you may have to restart the runtime to see the changes.