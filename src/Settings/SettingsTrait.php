<?php

namespace Jbtronics\SettingsBundle\Settings;

/**
 * This trait has common functionality for all settings classes.
 * It also forbids calling the constructor directly.
 */
trait SettingsTrait
{
    use BlockConstructorTrait;
}