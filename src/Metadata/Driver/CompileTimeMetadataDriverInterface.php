<?php

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Metadata\Driver;

use Jbtronics\SettingsBundle\Settings\Settings;

/**
 * An additional interface for metadata drivers, that can provide metadata at compile time for services that should be
 * registered in the container. This is required for settings that should be dependency-injectable,
 * as the container needs to know basic info about the service at compile time.
 * An alternative to this setting is to use a special compiler pass that registers the service by hand.
 */
interface CompileTimeMetadataDriverInterface
{
    /**
     * Returns metadata for all services, that should be registerable in the container. This is required for
     * settings that should be dependency-injectable, as the container needs to know basic info about the service at
     * compile time.
     * @param  array  $containerParameters The parameters of the container at compile time, which can be used to retrieve configuration values, etc.
     * @return array<string, Settings> A list of service metadata, keyed by class name and the settings attribute configuration for the service to be injected
     * @phpstan-return array<class-string, Settings>
     */
    public static function getServiceMetadataForContainerCompilation(array $containerParameters): array;
}