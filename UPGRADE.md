# Upgrade guide

This file describes the changes, which you need to do when upgrading to a new major version.
The newest version is at the top.

## Upgrade from 3.x to 4.0

### Minimum requirements raised

The bundle now requires at least PHP 8.2 and Symfony 7.0. Support for PHP 8.1 and Symfony 6.4 was dropped.

### Default settings names only remove the `Settings` suffix

If no `name` is set explicitly on a settings class, the name is generated from the short class name. Before 4.0,
**every** occurrence of `Settings` was removed. Now only the `Settings` suffix is removed:

| Class name             | Name in 3.x    | Name in 4.0            |
|------------------------|----------------|------------------------|
| `MailSettings`         | `mail`         | `mail` (unchanged)     |
| `UserSettingsOverride` | `useroverride` | `usersettingsoverride` |
| `SettingsForMail`      | `formail`      | `settingsformail`      |
| `Settings`             | (empty)        | `settings`             |

**Only classes without an explicit `name`, whose short class name contains `Settings` somewhere other than at the end
(or is exactly `Settings`), are affected.** Classes whose name only ends with `Settings` keep their name.

The name is used as the storage key, and to retrieve the settings by name (e.g. `SettingsManagerInterface::get('name')`,
the `settings_instance('name')` Twig function or the `%env(settings:name:parameter)%` env var processor).
For affected classes, the previously persisted data will not be found anymore (the default values are used instead),
and lookups by the old name will fail.

#### How to upgrade

Set the old name explicitly on every affected class, to keep the old storage key and name:

```php
#[Settings(name: 'useroverride')]
class UserSettingsOverride
{
    // ...
}
```

or, if you configure your settings via YAML:

```yaml
App\Settings\UserSettingsOverride:
    name: 'useroverride'
```

Alternatively, you can keep the new name, and move the persisted data in your storage backend from the old key to the
new one.
