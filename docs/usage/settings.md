---
title: Keybindings
layout: default
parent: Usage
---

# Defining settings

Settings are defined as classes, which contain the parameters as properties. The classes are marked with the `#[Settings]` attribute, which makes them
managable by the settings-bundle. Besides the attribute, the class is basically just a normal PHP class, which can contain any kind of methods and properties.
Only classes with the `#[Settings]` attribute and which are contained in on of the configured settings directories will be usable by the settings-bundle. By default, this means that you should put them into the `src/Settings` directory of your symfony project (or a subfolder of it).

Settings classes should be suffixed by `Settings` (e.g. `MySettings`), but this is not required.

The properties of the class, which should be filled by the settings-bundle, are marked with the `#[SettingsParameter]` attribute. This attribute contains information about how the data of the parameter should be mapped to normalized data for the storage adapter and how the parameter should be rendered in forms, etc.

```php
<?php
// src/Settings/TestSettings.php

namespace App\Settings;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsTrait;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Symfony\Component\Validator\Constraints as Assert;


#[Settings] // The settings attribute makes a simple class to settings
class TestSettings {
    use SettingsTrait; // Disable constructor and __clone methods

    //The property is public here for simplicity, but it can also be protected or private
    #[SettingsParameter(type: StringType::class, label: 'My String', description: 'This value is shown as help in forms.')]
    public string $myString = 'default value'; // The default value can be set right here in most cases

    #[SettingsParameter(type: IntType::class, label: 'My Integer', description: 'This value is shown as help in forms.')]
    #[Assert\Range(min: 5, max: 10,)] // You can use symfony/validator to restrict possible values
    public ?int $myInt = null;
}
```

The parameter values are filled by the settings-bundle via reflection. Therefore the properties can be either public, where you access the properties directly, or protected/private, where you have to use the getter/setter methods. Please note that the properties get accessed directly via reflection, so that the getter/setter methods are not called.

The only useful way to retrieve an instance of a settings class is via the SettingsManager. You can not instantiate the class directly, as it would not be initialized correctly. Therefore you should add the `SettingsTrait` to your settings class, which disables the constructor, `__clone` method, etc. so that you can not instantiate the class directly by accident. If you need to perform some more complex initialization of your settings class, see below how to do that properly.

## Defining default values for parameters

The default values for parameters can be set directly in the property declaration in most cases (by directly assigning the value in the declaration e.g. `private int $property = 4;`).

If you require more complex initialization, which can not be done directly in the declaration (e.g. create an object), your settings class can implement the `ResettableSettingsInterface` interface and the `resetToDefaultValues()` method. This method will be called by the settings-bundle everytime a new instance of the settings class is created or the settings are reset to default values. It is called after all properties have been initialized/reset to the default values.

```php
<?php
// src/Settings/ResettableSettings.php

namespace App\Settings;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsTrait;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Symfony\Component\Validator\Constraints as Assert;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;


#[Settings] // The settings attribute makes a simple class to settings
class ResettableSettings implements ResettableSettings
{
    use SettingsTrait; 

    #[SettingsParameter(type: StringType::class, label: 'My String', description: 'This value is shown as help in forms.')]
    public string $myString; // We set the default value later

    public function resetToDefaultValues(): void
    {
        //Reset all properties without default values:
        $this->myString = 'default value';
    }
}
```
