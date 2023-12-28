<?php

namespace Jbtronics\SettingsBundle\Schema;

use Jbtronics\SettingsBundle\Metadata\Settings;
use Jbtronics\SettingsBundle\Metadata\SettingsParameter;
use Symfony\Contracts\Cache\CacheInterface;

final class SchemaManager implements SchemaManagerInterface
{
    private array $schemas_cache = [];

    private const CACHE_KEY_PREFIX = 'jbtronics_settings.schema.';

    public function __construct(private readonly CacheInterface $cache, private readonly bool $debug_mode)
    {
    }

    public function isSettingsClass(string $className): bool
    {
        //Check if the given class contains a #[ConfigClass] attribute.
        //If yes, return true, otherwise return false.

        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        return count($attributes) > 0;
    }

    public function getSchema(string $className): SettingsSchema
    {
        //Check if the schema for the given class is already cached.
        if (isset($this->schemas_cache[$className])) {
            return $this->schemas_cache[$className];
        }

        if ($this->debug_mode) {
            $schema = $this->getSchemaUncached($className);
        } else {
            $schema = $this->cache->get(self::CACHE_KEY_PREFIX . md5($className) , function () use ($className) {
                return $this->getSchemaUncached($className);
            });
        }

        $this->schemas_cache[$className] = $schema;
        return $schema;
    }

    private function getSchemaUncached(string $className): SettingsSchema
    {


        //If not, create it and cache it.

        //Retrieve the #[ConfigClass] attribute from the given class.
        $reflClass = new \ReflectionClass($className);
        $attributes = $reflClass->getAttributes(Settings::class);

        if (count($attributes) < 1) {
            throw new \LogicException(sprintf('The class "%s" is not a config class. Add the #[ConfigClass] attribute to the class.', $className));
        }
        if (count($attributes) > 1) {
            throw new \LogicException(sprintf('The class "%s" has more than one ConfigClass atrributes! Only one is allowed', $className));
        }

        $classAttribute = $attributes[0]->newInstance();
        $parameters = [];

        //Retrieve all ConfigEntry attributes on the properties of the given class
        $reflProperties = $reflClass->getProperties();
        foreach ($reflProperties as $reflProperty) {
            $attributes = $reflProperty->getAttributes(SettingsParameter::class);
            //Skip properties without a ConfigEntry attribute
            if (count ($attributes) < 1) {
                continue;
            }
            if (count($attributes) > 1) {
                throw new \LogicException(sprintf('The property "%s" of the class "%s" has more than one ConfigEntry atrributes! Only one is allowed', $reflProperty->getName(), $className));
            }

            //Add it to our list
            /** @var SettingsParameter $propertyAttribute */
            $propertyAttribute = $attributes[0]->newInstance();
            $parameters[] = new ParameterSchema(
                className: $className,
                propertyName: $reflProperty->getName(),
                type: $propertyAttribute->type,
                name: $propertyAttribute->name,
                label: $propertyAttribute->label,
                description: $propertyAttribute->description,
                extra_options: $propertyAttribute->extra_options,
            );
        }

        //Now we have all infos required to build our schema
        return new SettingsSchema($className, $parameters);
    }
}