---
title: Parameter types
layout: default
parent: Usage
---

# Parameter types

Parameter types are services, which convert the data of a parameter in a settings object to the normalized format for the storage adapter and vice versa. They are also resonsible for giving information about how to render the parameter in forms, etc.

In many cases you do not need to create your own parameter types, and dont even have to explicitly give a parameter type for a property. The bundle comes with a few default parameter types, which are automatically used for the most common property types in PHP (see below).

## Built-in parameter types

Following parameter types are built-in and handle the most common property types in PHP:

* `StringType`: Maps a string property
* `IntType`: Maps an integer property
* `BoolType`: Maps a boolean property
* `FloatType`: Maps a float property
* `EnumType`: Maps an (backed) enum property. The enum must be backed, and the backed values are used to store the enum values in the storage backend. If the enumtype is not configured automatically, you have to pass the enum class as extra option to the parameter attribute (e.g. `#[SettingsParameter(type: EnumType::class, options: ['class' => MyEnum::class])]`). By default this should be setup automatically by the bundle in most cases.

## Automatic parameter type detection

For many properties the bundle can automatically detect the correct parameter type based on the declared property type. This is done by the `ParameterTypeGuesserInterface` service. Decorate this service to add your own parameter type guesser logic.
The service also keeps care of passing the correct options to the parameter type, like the enum class for enum types, etc., if they can be derived from the property type.

The following table shows the default mapping of property types to parameter types:

| Property type | Parameter type |
|---------------|----------------|
| string        | StringType     |
| int           | IntType        |
| bool          | BoolType       |
| float         | FloatType      |
| enum / BackedEnum          | EnumType       |

A nullable property type is mapped to the same parameter type, but with the `nullable` option of the parameter metadata set to `true`.

## Creating custom parameter types

You can create your own parameter types by creating a new service implementing the `ParameterTypeInterface`. This way you can also create parameter types for more complex properties, like objects, arrays, etc: 

The interface requires two methods, which converts the value from the PHP object to a normalized format for the storage adapter and vice versa. As a second parameter you get the Metadata of the parameter, which is currently converted, so you can access its options and depend the behavior of the parameter type on them.

```php
use Jbtronics\SettingsBundle\Schema\ParameterSchema;

class MyType implements ParameterTypeInterface
{

    public function convertPHPToNormalized(
        mixed $value,
        ParameterSchema $parameterSchema,
    ): int|string|float|bool|array|null {
        //Convert the value in the PHP object to the normalized format for the storage adapter

        return $normalizedValue;
    }

    public function convertNormalizedToPHP(
        float|int|bool|array|string|null $value,
        ParameterSchema $parameterSchema,
    ): ?bool {
        //Convert the value from the normalized format for the storage adapter to the PHP object

        return $phpValue;
    }

}
```

If you wanna define some default behavior for form rendering on your parameter type, you can also implement the `ParameterTypeWithFormDefaultsInterface` interface. It basically allows you to define a default form type and configure its options. This way you can define a default form type for your parameter type, which is used if no form type is explicitly given for the parameter.

```php

class MyType implements ParameterTypeInterface, ParameterTypeWithFormDefaultsInterface
{
    public function getFormType(ParameterSchema $parameterSchema): string
    {
        //Return the class name of the form type to use
        return CheckboxType::class;
    }

    public function configureFormOptions(OptionsResolver $resolver, ParameterSchema $parameterSchema): void
    {
        //Configure the default options of the form type via the options resolver

        //The checkbox should be allowed to be false
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}