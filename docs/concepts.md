---
layout: default
title: Concepts
nav_order: 2
---

# Concepts

This section describes the basic concept of the settings-bundle.

## Settings

Settings are the main concept and the core of the settings-bundle.
To be typesafe, each settings is implemented as a class, which contains a set of parameters as properties.
Instances of this class are retrieved via the SettingsManager and contain the current configuration.

The idea behind the settings class is that they contain all necessary information and configuration of the settings as code metadata
in form of attributes. This allows to make all necessary changes in a single file.

## Parameters

Parameters are properties inside a settings class, whose values are managed by the settings-bundle. The metadata of the parameters are used to determine how to handle the parameters (e.g. which parameter type to use, how to render them in forms, etc.).

## Schemas

The Settings and ParameterSchemas are a representation of the metadata of settings classes. They dont contain any data, but describe the structure of settings, their configuration and behavior. The schemas are used in other parts of the bundle to determine what to do.

The schemas for specific settings classes can be retrieved by the `SchemaManagerInterface` service.

## Storage adapters
The settings-bundle is designed to be storage backend. That means that almost all functionality is implemented independently of a concrete storage backend. Therefore you can use all kind of different storage backends (files, database, etc.) and even implement your own storage backend without changing other parts of the bundle.

The concrete storage implentation is done via storage adapters, which must implement the `StorageAdapterInterface`. The bundle comes with a few default storage adapters, but you can also implement your own storage adapters.