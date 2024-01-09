---
title: Parameter types
layout: default
parent: Usage
---

# Parameter types

Parameter types are services, which convert the data of a parameter in a settings object to the normalized format for the storage adapter and vice versa. They are also resonsible for giving information about how to render the parameter in forms, etc.

## Built-in parameter types

Following parameter types are built-in and handle the most common property types in PHP:

* `StringType`: Maps a string property
* `IntType`: Maps an integer property
* `BoolType`: Maps a boolean property

## Creating custom parameter types

You can create your own parameter types by creating a new service implementing the `ParameterTypeInterface`. This way you can also create parameter types for more complex properties, like objects, arrays, etc.

TODO: Add example
