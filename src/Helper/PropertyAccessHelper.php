<?php

namespace Jbtronics\SettingsBundle\Helper;

use PHPUnit\Framework\Assert;

/**
 * Helpers to access private and protected properties
 *
 * This class contains code adapted from nyholm/NSA package (https://github.com/Nyholm/NSA/blob/master/src/NSA.php)
 * from Tobias Nyholm.
 * It is licensed under the MIT license (https://github.com/Nyholm/NSA/blob/master/LICENSE)
 */
class PropertyAccessHelper
{
    /**
     * Get a reflection class that has this property.
     *
     * @param string $class
     * @param string $propertyName
     *
     * @return \ReflectionClass|null
     *
     * @throws \InvalidArgumentException
     */
    protected static function getReflectionClassWithProperty(string $class, string $propertyName): ?\ReflectionClass
    {
        $refl = new \ReflectionClass($class);
        if ($refl->hasProperty($propertyName)) {
            return $refl;
        }

        if (false === $parent = get_parent_class($class)) {
            // No more parents
            return null;
        }

        return self::getReflectionClassWithProperty($parent, $propertyName);
    }

    /**
     * Get an reflection property that you can access directly.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return \ReflectionProperty
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException           if the property is not found on the object
     */
    public static function getAccessibleReflectionProperty(object|string $objectOrClass, string $propertyName): \ReflectionProperty
    {
        $class = $objectOrClass;
        if (!is_string($objectOrClass)) {
            $class = get_class($objectOrClass);
        }

        if (null === $refl = static::getReflectionClassWithProperty($class, $propertyName)) {
            throw new \LogicException(sprintf('The property %s does not exist on %s or any of its parents.', $propertyName, $class));
        }

        $property = $refl->getProperty($propertyName);

        if (!$property->isStatic() && !is_object($objectOrClass)) {
            throw new \LogicException('Can not access non-static property without an object.');
        }

        return $property;
    }

    /**
     * Get a property of an object. If the property is static you may provide the class name (including namespace)
     * instead of an object.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function getProperty(object|string $objectOrClass, string $propertyName): mixed
    {
        $reflectionProperty = static::getAccessibleReflectionProperty($objectOrClass, $propertyName);

        $object = $objectOrClass;
        if ($reflectionProperty->isStatic()) {
            $object = null;
        } elseif (is_string($objectOrClass)) {
            $object = (new \ReflectionClass($objectOrClass))->newInstanceWithoutConstructor();
        }

        return $reflectionProperty->getValue($object);
    }

    /**
     * Set a property to an object. If the property is static you may provide the class name (including namespace)
     * instead of an object.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     * @param mixed         $value
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function setProperty(object|string $objectOrClass, string $propertyName, mixed $value): void
    {
        $reflectionProperty = static::getAccessibleReflectionProperty($objectOrClass, $propertyName);

        $object = $objectOrClass;
        if ($reflectionProperty->isStatic()) {
            $object = null;
        } elseif (is_string($objectOrClass)) {
            $object = (new \ReflectionClass($objectOrClass))->newInstanceWithoutConstructor();
        }

        $reflectionProperty->setValue($object, $value);
    }

    /**
     * Get all property reflection objects of a class.
     *
     * @param object|string $objectOrClass
     * @return \ReflectionProperty[] of strings
     * @throws \InvalidArgumentException
     */
    public static function getProperties(object|string $objectOrClass): array
    {
        $class = $objectOrClass;
        if (!is_string($objectOrClass)) {
            $class = get_class($objectOrClass);
        }

        $refl = new \ReflectionClass($class);
        $properties = $refl->getProperties();

        // check parents
        while (false !== $parent = get_parent_class($class)) {
            $parentRefl = new \ReflectionClass($parent);
            $properties = array_merge($properties, $parentRefl->getProperties());
            $class = $parent;
        }

        return $properties;
    }
}