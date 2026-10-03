<?php

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Proxy;

/**
 * Helper class providing utility methods for working with lazy objects. This supports both the native lazy objects
 * of PHP 8.4+ and the legacy proxies (pre-PHP 8.4).
 * @internal
 */
final class LazyObjectHelper
{

    /**
     * Checks if the given object is a lazy object (native or legacy proxy), which is not initialized yet.
     * If the object is not a lazy object, it returns false.
     */
    public static function isUninitializedLazyObject(object $instance): bool
    {
        //PHP native way
        if (PHP_VERSION_ID >= 80400 && (new \ReflectionClass($instance))->isUninitializedLazyObject($instance)) {
            return true;
        }

        //Fallback for older PHP versions
        return $instance instanceof SettingsProxyInterface && self::isLegacyProxyUninitialized($instance);
    }

    /**
     * Checks if a legacy (pre-PHP 8.4) proxy is still uninitialized.
     * If the object is not a legacy proxy, it returns false.
     */
    private static function isLegacyProxyUninitialized(object $instance): bool
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
