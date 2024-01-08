# Settings bundle

Settings-bundle is a symfony bundle that let you manage your application settings on the frontend side.

## Introduction
By default symfony is mostly configured by parameters in configuration files, where a recompilation of the container is required, or via environment variables, which can not be easily changed by the application itself. 

However you often want administrators and users of your application let change settings and configuration of your application. This bundle provides a simple way to do this. Unlike other bundles with a similar goal, this bundle tries to be as modular as possible and to be as type-safe as possible. Therefore you define your Settings as a class, and access objects of this class in your application, instead of doing simple key-value lookups with mixed return types.

All relevant definitions of settings are done directly in the settings class via metadata attributes. This makes it easy to understand and maintain the settings. The bundle also provides a simple way to generate forms to change the settings, which can be easily integrated into your application.

## Features
* Class based settings, which get easily managed by the bundle
* Type-safe access to settings
* Easy to use API
* Various storage backends, like database, json files, PHP files, etc. (custom backends can be easily implemented)
* Use symfony/validator to easily restrict possible values of settings parameters
* Automatically generate forms to change settings
* Profiler integration for easy debugging

## Installation

Add the bundle to your symfony project via composer:
```bash
composer require jbtronics/settings-bundle
```

If you are using symfony flex, the bundle should be automatically enabled. Otherwise you have to add the bundle to your `config/bundles.php` file:
```php
return [
    // ...
    Jbtronics\SettingsBundle\SettingsBundle::class => ['all' => true],
];
```

## Usage

Settings classes are simple PHP classes, which are annotated with the `#[Settings]` attribute. They must live in the path configured to store settings classes (normally `src/Settings`), in your symfony project. The bundle will automatically find and register all settings classes in this directory.

The properties of the classes are used for storing the different data. Similar to the `#[ORM\Column]` attribute of doctrine, you can use the `#[SettingsParameter]` attribute to make a class property to a managed parameter. The properties can be public, protected or private (as SettingsBundle accesses them via reflection), but you have some kind of possibility to access the properties to get/set the configuration parameters in your software.
You have to configure, which type mapper should be used to map the normalized data from the storage backend to the type of property. The bundle comes with a few default type mappers, but you can also implement your own type mappers.

```php
<?php
// src/Settings/TestSettings.php

namespace App\Settings;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Symfony\Component\Validator\Constraints as Assert;


#[Settings] // The settings attribute makes a simple class to settings
class TestSettings {

    //The property is public here for simplicity, but it can also be protected or private
    #[SettingsParameter(type: StringType::class, label: 'My String', description: 'This value is shown as help in forms.')]
    public string $myString = 'default value'; // The default value can be set right here in most cases

    #[SettingsParameter(type: IntType::class, label: 'My Integer', description: 'This value is shown as help in forms.')]
    #[Assert\Range(min: 5, max: 10,)] // You can use symfony/validator to restrict possible values
    public ?int $myInt = null;
}
```


## License

SettingsBundle is licensed under the MIT License.
This mostly means that you can use Part-DB for whatever you want (even use it commercially)
as long as you retain the copyright/license information.

See [LICENSE](https://github.com/jbtronics/settings-bundle/blob/master/LICENSE) for more information.