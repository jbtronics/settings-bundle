---
title: Using environment variables together with settings
layout: default
parent: Usage
nav_order: 9
---

# Using environment variables together with settings

If you are configuring your application using symfony config and container parameters, you can use environment variables
to easily configure applications deployed via docker or other containerized environments.

This settings-bundle provides a way to use environment variables to initialize or override parameters in settings classes,
to make it easier to configure your application in a automatically deployed environment.

## Usage

{: .warning }
> Be sure to read the limitations section below!

You can use the `envVar` option on the `#[SettingsParameter]` attribute on settings parameter, to specify an environment
variable which should be mapped to the parameter. You can either use pass the env var directly here or use the [environment
variable processors defined by symfony](https://symfony.com/doc/current/configuration/env_var_processors.html) to preprocess
the data or convert it to a different type.

```php
#[SettingsParameter(envVar: 'ENV_VAR1')
private string $myParameter = 'default';


#[SettingsParameter(envVar: 'bool:ENV_VAR2')
private ?bool $boolParameter = null;
```

If the environment variable is not set, everything will behave as if the parameter was not set in SettingsParameter attribute.
If the environment variable is set however, the value of the envVar will be read and used to initialize the parameter.
By default, this means that if the environment variable is set, the parameter value will be set to the value of the environment
variable, if no user setting was persisted in the storage yet. For example, if if you set `ENV_VAR1` to `value1` and the
`ENV_VAR2` to `true`, the parameters will be initialized to `value1` and `true` respectively.

There are different modes on how the environment variable should be used to initialize the parameter. You can specify the
mode by passing `EnvVarMode` enum value to the  `envVarMode` option on the `#[SettingsParameter]` attribute. 
The following modes are available:

* `EnvVarMode::INITIAL` (default): The environment variable will only be used to set the parameter value if the parameter
 was not persisted in the storage yet. If the parameter was already persisted in the storage, the environment variable will
 be ignored. This allows to somehow "seed" the settings with some useful default values configured via environment variables,
 but still allows the user to override them with their own settings using the WebUI later.
* `EnvVarMode::OVERWRITE`: The parameter value will always be set to the value of the environment variable, even if the parameter
 was already persisted in the storage before. This makes it unable to change the value of the parameter using the WebUI. You must
 remove the environment variable to allow the user to change the value of the parameter using the WebUI. The value of the environment
 variable is never persisted in the storage. Meaning that if you remove the environment variable, the parameter will be reset to the
 value persisted in the storage before.
* `EnvVarMode::OVERWRITE_PERSIST`: Same as `EnvVarMode::OVERWRITE`, however the value of the environment variable is persisted in the
  storage. This means that if you remove the environment variable, the value will be still be the loaded from the storage afterwards
  and can be changed using the WebUI.

## Environment variable mappers

By default, environment variables are given as strings and map to string properties. Using the env var processors, you can
convert the string to other simple datatypes like bool, int, float, etc. If you want to convert the string from the env
var to a more complex datatype, you can use the `envVarMapper` option on the `#[SettingsParameter]` attribute to specify
a mapper for the environment variable.

You can either pass a class name to an ParameterType here, which reuses the logic of the parameter type to convert the
string to the desired type. Or you pass a callable to a (static) function, which takes the output of the env var processor
and converts it to the desired type.

```php
#[SettingsParameter(envVar: 'bool:ENV_VAR3')
private ?\DateTime $dateTimeParam = null;
```

## Forms

If you are using the form generation feature of this bundle, the fields of parameters, which are overwritten by env vars
will be disabled in the form, as these values are overwritten by the env vars immediately after hydrating the settings.

A help text will be displayed in the form, which shows the user which env var he needs to remove to be able to change the
settings again.

If you implement your own form, you can use the  `isEnvVarOverwritten()` method on the SettingsManagerInterface to check
if a parameter is overwritten by an env var.

## Limitations

You must ensure that the environment variables are converted to the correct type, which the parameter expects. Otherwise,
you will run into exceptions.

The environment variable values are not validated by the constraints. You must ensure that for yourself. In general, you
should only use environment variables for simple values, which do not need to be validated by complex constraints.

If you are using the EnvVarMode::OVERWRITE mode, the env vars are only applied during the hydration of the settings class.
If you change the parameters after retrieving the settings object from the SettingsManager, the env vars will only be applied
again, if you rehydrate the settings object (e.g. `reload` method on the SettingsManagerInterface). If you want to change
the  parameters manually, you should therefore use the `isEnvVarOverwritten()` method to check if the parameter is 
overwritten by env vars.


