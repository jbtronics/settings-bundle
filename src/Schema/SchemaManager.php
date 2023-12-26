<?php

namespace Jbtronics\UserConfigBundle\Schema;

use Jbtronics\UserConfigBundle\Metadata\ConfigClass;
use Jbtronics\UserConfigBundle\Metadata\ConfigEntry;

final class SchemaManager implements SchemaManagerInterface
{

    private array $schemas_cache = [];

    public function isConfigClass(string $className): bool
    {
        //Check if the given class contains a #[ConfigClass] attribute.
        //If yes, return true, otherwise return false.

        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(ConfigClass::class);

        return count($attributes) > 0;
    }

    public function getSchema(string $className): ConfigSchema
    {
        //Check if the schema for the given class is already cached.
        if (isset($this->schemas_cache[$className])) {
            return $this->schemas_cache[$className];
        }

        //If not, create it and cache it.

        //Retrieve the #[ConfigClass] attribute from the given class.
        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(ConfigClass::class);

        if (count($attributes) < 1) {
            throw new \LogicException(sprintf('The class "%s" is not a config class. Add the #[ConfigClass] attribute to the class.', $className));
        }
        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The class "%s" has more than one ConfigClass atrributes! Only one is allowed', $className));
        }

        $classAttribute = $attributes[0]->newInstance();
        $propertyAttributes = [];

        //Retrieve all ConfigEntry attributes on the properties of the given class
        $reflProperties = $reflClass->getProperties();
        foreach ($reflProperties as $reflProperty) {
            $attributes = $reflProperty->getAttributes(ConfigEntry::class);
            //Skip properties without a ConfigEntry attribute
            if (count ($attributes) < 1) {
                continue;
            }
            if (count($attributes) > 1) {
                throw new \LogicException(sprintf('The property "%s" of the class "%s" has more than one ConfigEntry atrributes! Only one is allowed', $reflProperty->getName(), $className));
            }

            //Add it to our list
            $propertyAttributes[$reflProperty->getName()] = $attributes[0]->newInstance();
        }

        //Now we have all infos required to build our schema
        $schema = new ConfigSchema($className, $classAttribute, $propertyAttributes);
        $this->schemas_cache[$className] = $schema;
        return $schema;
    }
}