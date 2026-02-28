<?php

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Proxy;

/**
 * Helper class providing utility methods for working with legacy proxies (pre-PHP 8.4).
 * @internal
 */
final class LegacyProxyHelper
{

    /**
     * Checks if a legacy (pre-PHP 8.4) proxy is still uninitialized.
     * If the object is not a legacy proxy, it returns false.
     */
    public static function isLegacyProxyUninitialized(object $instance): bool
    {
        if (!method_exists($instance, 'isLazyObjectInitialized')) {
            return false;
        }

        $method = new \ReflectionMethod($instance, 'isLazyObjectInitialized');
        if ($method->getNumberOfParameters() >= 1) {
            return !$instance->isLazyObjectInitialized(false);
        }

        return !$instance->isLazyObjectInitialized();
    }
}