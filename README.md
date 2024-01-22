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
    use SettingsTrait; // Disable constructor and __clone methods

     //The properties are public here for simplicity, but it can also be protected or private

    //In many cases this attribute with zero config is enough, the type mapper is then derived from the declared type of the property
    #[SettingsParameter()]
    public string $myString = 'default value'; // The default value can be set right here in most cases

    //Or you can explicitly set the type mapper and some options
    #[SettingsParameter(type: IntType::class, label: 'My Integer', description: 'This value is shown as help in forms.')] 
    #[Assert\Range(min: 5, max: 10,)] // You can use symfony/validator to restrict possible values
    public ?int $myInt = null;
}
```

The main way to work with settings is to use the `SettingsManagerInterface` service. It offers a `get()` method, which allows to retrieve the current settings for a given settings class. If not loaded yet, the manager will load the desired settings from the storage backend (or initialize a fresh instance with default values). The instances are cached, so that the manager will always return the same instance for a given settings class. The manager also offers a `save()` method, which allows to save the current settings to the storage backend and persist the changes.

```php

use Jbtronics\SettingsBundle\Settings\SettingsManagerInterface;

class ExampleService {
    public function __construct(private SettingsManagerInterface $settingsManager) {}

    public function accessAndSaveSettings(): void
    {
        /** @var TestSettings $settings This is an instance of our previously defined setting class, containing the stored settings */
        $settings = $this->settingsManager->get(TestSettings::class);

        //To read the current settings value, just access the property
        dump('My string is: ' . $settings->myString);

        //To change the settings, just change the property (or call the setter)
        $settings->myString = 'new value';

        //And save the settings to the storage backend
        $this->settingsManager->save($settings);


        //You can also access the settings via a given name (which is the part before the "Settings" suffix of the class name in lowercase, by default)
        $settings = $this->settingsManager->get('test');

        //You can set an invalid value to the parameters
        $settings->myInt = 42;

        //But the bundle will throw an exception, when you try to save the settings
        $this->settingsManager->save($settings); // Throws an excpetion
    }
}

```

### Forms

The bundle can automatically generate forms to change settings classes. This is done via the `SettingsFormFactoryInterface`, which creates a form builder containing fields to edit one or more settings classes. You can also render just a subset of the settings. Validation attributes are checked and mapped to form errors. This way you can easily create a controller, to let users change the settings:

```php
<?php

class SettingsFormController {

    public function __construct(
        private SettingsManagerInterface $settingsManager,
        private SettingsFormFactoryInterface $settingsFormFactory,
        ) {}

    #[Route('/settings', name: 'settings')]
    public function settingsForm(Request $request): Response
    {
        //Create a builder for the settings form
        $builder = $this->settingsFormFactory->createSettingsFormBuilder(TestSettings::class);

        //Add a submit button, so we can save the form
        $builder->add('submit', SubmitType::class);

        //Create the form
        $form = $builder->getForm();

        //Handle the form submission
        $form->handleRequest($request);

        //If the form was submitted and the data is valid, then it
        if ($form->isSubmitted() && $form->isValid()) {
            //Save the settings
            $this->settingsManager->save();
        }

        //Render the form
        return $this->render('settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

Form rendering can be customized via the Parameter attributes. See documentation for full info.

### Twig templates

In twig templates you can access the settings via the `settings_instance()` function, which behaves like the `SettingsManagerInterface::get()` function and returns the current settings instance:

```twig
{# @var settings \App\Settings\TestSettings #}
{% set settings = settings_instance('test') %}
{{ dump(settings) }}

{# or directly #}
{{ settings_instance('test').myString }}
```

## License

SettingsBundle is licensed under the MIT License.
This mostly means that you can use Part-DB for whatever you want (even use it commercially)
as long as you retain the copyright/license information.

See [LICENSE](https://github.com/jbtronics/settings-bundle/blob/master/LICENSE) for more information.