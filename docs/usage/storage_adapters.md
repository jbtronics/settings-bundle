---
title: Storage adapters
layout: default
parent: Usage
---

# Storage adapters

The settings-bundle is designed to be storage backend. That means that almost all functionality is implemented independently of a concrete storage backend. Therefore you can use all kind of different storage backends (files, database, etc.) and even implement your own storage backend without changing other parts of the bundle.

The concrete storage implentation is done via storage adapters, which must implement the `StorageAdapterInterface`. The bundle comes with a few default storage adapters, but you can also implement your own storage adapters.

## Built-in storage adapters

Following storage adapters are built-in:

* `InMemoryStorageAdapter`: Stores the settings in memory (RAM). The settings are not persistet and will be lost after the current request. This storage adapter is mainly used for testing.
* `JsonFileStorageAdapter`: Stores the settings in a JSON file. The file is created in the configured directory for settings files. The file name is the (short) name of the settings class with the `.json` suffix (e.g. `test.json`).


## Creating custom storage adapters

You can create your own storage adapters by creating a new service implementing the `StorageAdapterInterface`. This way you can also create storage adapters for more complex storage backends, like databases, etc.

TODO: Add example 