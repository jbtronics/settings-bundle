<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Proxy;

use ReflectionClass;
use Symfony\Component\VarExporter\LazyObjectInterface;

final class LazyObjectTestHelper
{
    /**
     * Checks if the given object is a lazy object, no matter if its using PHP native objects or the old implementation.
     * @param  object  $obj
     * @return bool
     */
    public static function isLazyObject(object $obj): bool
    {
        if ($obj instanceof LazyObjectInterface){
            return true;
        }

        if (PHP_VERSION_ID >= 80400) {
            return (new ReflectionClass($obj))->isUninitializedLazyObject($obj);
        }

        //If we reach here, the object is not a lazy object
        return false;
    }

    /**
     * Check if the given lazy object is initialized. If the object is not a lazy object, it is considered initialized.
     * @param  object  $obj
     * @param  bool  $partial
     * @return bool
     */
    public static function isLazyObjectInitialized(object $obj, bool $partial = false): bool
    {
        if ($obj instanceof LazyObjectInterface){
            return $obj->isLazyObjectInitialized($partial);
        }

        if (PHP_VERSION_ID >= 80400) {
            return !(new ReflectionClass($obj))->isUninitializedLazyObject($obj);
        }

        //If we reach here, the object is not a lazy object, so it is considered initialized
        return true;
    }
}