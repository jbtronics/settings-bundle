<?php

namespace Jbtronics\SettingsBundle\Settings;

/**
 * This trait makes the constructor private (and throws an exception when called).
 * Settings classes should not be instantiated directly, but only via the SettingsManager.
 */
trait BlockConstructorTrait
{
    private function __construct()
    {
        throw new \LogicException("Settings should not be instantiated directly. Use the SettingsManager instead.");
    }
}