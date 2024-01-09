---
title: Twig
layout: default
parent: Usage
---

# Twig integration

The settings-bundle comes with a twig extension, which allows you to easily access settings in twig templates.

## settings_instance()

The `settings_instance()` twig function allows you to access the current settings instance for a given settings class. It behaves like the `SettingsManagerInterface::get()` function and returns the current settings instance. You can either pass the full class name or the name of the settings class (either set explicitly in the `#[Settings]` attribute or the part before the "Settings" suffix of the class name in lowercase, by default).

{% raw %}
```twig
{# @var settings \App\Settings\TestSettings #}
{% set settings = settings_instance('test') %}
{{ dump(settings) }}

{# or directly #}
{{ settings_instance('test').myString }}
```
{% endraw %}

If you use an IDE like PHPstorm which offers autocompletion for twig templates, it might be helpful to first assign the settings instance to a variable, and add an type annotation to it, so the IDE can offer autocompletion.