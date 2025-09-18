<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Exception\ParameterDataNotCloneableException;
use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Metadata\MetadataManager;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Settings\CloneAndMergeAwareSettingsInterface;
use Jbtronics\SettingsBundle\Settings\ResettableSettingsInterface;
use PhpParser\Node\Param;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * @internal
 */
final class SettingsCloner implements SettingsClonerInterface
{
    public function __construct(
        private readonly MetadataManager $metadataManager,
        private readonly ProxyFactoryInterface $proxyFactory,
    )
    {
    }

    public function createClone(object $settings): object
    {
        $embedded_clones = [];
        return $this->createCloneInternal($settings, $embedded_clones);
    }

    private function createCloneInternal(object $settings, array &$embeddedClones, ?object $existingInstance = null): object
    {
        $metadata = $this->metadataManager->getSettingsMetadata($settings);

        //Use reflection to create a new instance of the settings class
        $reflClass = new \ReflectionClass($metadata->getClassName());
        $clone = $existingInstance ?? $reflClass->newInstanceWithoutConstructor();

        //Call the settingsReset method on the new instance to ensure that all properties are initialized.
        //This is especially important for non-parameter properties
        if ($clone instanceof ResettableSettingsInterface) {
            $clone->resetToDefaultValues();
        }

        //Iterate over all properties and copy them to the new instance
        foreach ($metadata->getParameters() as $parameter) {
            $oldVar = PropertyAccessHelper::getProperty($settings, $parameter->getPropertyName());
            $newVar = $this->cloneDataIfNeeded($oldVar, $parameter);

            //Set the property on the new instance
            PropertyAccessHelper::setProperty($clone, $parameter->getPropertyName(), $newVar);
        }

        //Add the clone to the list of embedded clones, so that we can access it in other iterations of this method
        $embeddedClones[$metadata->getClassName()] = $clone;

        //Iterate over all embedded settings
        foreach ($metadata->getEmbeddedSettings() as $embeddedSetting) {
            //If the embedded setting was already cloned, we can reuse it
            if (isset($embeddedClones[$embeddedSetting->getTargetClass()])) {
                $embeddedClone = $embeddedClones[$embeddedSetting->getTargetClass()];
            } else {
                //Otherwise, we need to create a new clone, which we lazy load, via our proxy system
                $embeddedClone = $this->proxyFactory->createProxy($embeddedSetting->getTargetClass(), function (object $instance) use ($embeddedSetting, $settings, $embeddedClones) {
                    $this->createCloneInternal(PropertyAccessHelper::getProperty($settings, $embeddedSetting->getPropertyName()), $embeddedClones, $instance);
                });
            }

            //Set the embedded clone on the new instance
            PropertyAccessHelper::setProperty($clone, $embeddedSetting->getPropertyName(), $embeddedClone);
        }

        //If the settings class implements the CloneAndMergeAwareSettingsInterface, call the afterClone method
        if ($clone instanceof CloneAndMergeAwareSettingsInterface) {
            $clone->afterSettingsClone($settings);
        }

        return $clone;
    }

    public function mergeCopyInternal(object $copy, object $into, bool $recursive, array &$mergedClasses): object
    {
        $metadata = $this->metadataManager->getSettingsMetadata($copy);

        //Iterate over all properties and copy them to the new instance
        foreach ($metadata->getParameters() as $parameter) {
            $oldVar = PropertyAccessHelper::getProperty($copy, $parameter->getPropertyName());
            $newVar = $this->cloneDataIfNeeded($oldVar, $parameter);

            //Set the property on the new instance
            PropertyAccessHelper::setProperty($into, $parameter->getPropertyName(), $newVar);
        }

        $mergedClasses[$metadata->getClassName()] = $into;

        //If recursive mode is active, also merge the embedded settings
        if ($recursive) {
            foreach ($metadata->getEmbeddedSettings() as $embeddedSetting) {
                //Skip if the class was already merged
                if (isset($mergedClasses[$embeddedSetting->getTargetClass()])) {
                    continue;
                }

                $copyEmbedded = PropertyAccessHelper::getProperty($copy, $embeddedSetting->getPropertyName());

                //If the embedded setting is a lazy proxy and it was not yet initialized, we can skip it as the data was not modified
                if (PHP_VERSION_ID >= 80400 && (new \ReflectionClass($copyEmbedded)->isUninitializedLazyObject($copyEmbedded))) { //PHP native way
                    continue;
                }

                if ($copyEmbedded instanceof SettingsProxyInterface && $copyEmbedded instanceof LazyObjectInterface && !$copyEmbedded->isLazyObjectInitialized()) { //Fallback for older PHP versions
                    continue;
                }

                $intoEmbedded = PropertyAccessHelper::getProperty($into, $embeddedSetting->getPropertyName());

                //Recursively merge the embedded setting
                $this->mergeCopyInternal($copyEmbedded, $intoEmbedded, $recursive, $mergedClasses);
            }
        }

        //If the settings class implements the CloneAndMergeAwareSettingsInterface, call the afterMerge method
        if ($into instanceof CloneAndMergeAwareSettingsInterface) {
            $into->afterSettingsMerge($copy);
        }

        return $into;
    }

    public function mergeCopy(object $copy, object $into, bool $cascade = true): object
    {
        //If both instances are the same, we can return the copy directly
        if ($copy === $into) {
            return $copy;
        }

        //Ensure that both instances are of the same class
        $copyMetadata = $this->metadataManager->getSettingsMetadata($copy);
        $intoMetadata = $this->metadataManager->getSettingsMetadata($into);

        //If the classes are not the same, we can not merge them
        if ($copyMetadata->getClassName() !== $intoMetadata->getClassName()) {
            throw new \InvalidArgumentException(sprintf('The given copy (instance of %s) and into (instance of %s) instances are not of the same class', $copyMetadata->getClassName(), $intoMetadata->getClassName()));
        }

        $mergedClasses = [];
        return $this->mergeCopyInternal($copy, $into, $cascade, $mergedClasses);
    }

    /**
     * Checks if the given value should be cloned or not
     * @param  mixed  $value
     * @param  ParameterMetadata  $parameterMetadata
     * @return bool
     */
    private function shouldBeCloned(mixed $value, ParameterMetadata $parameterMetadata): bool
    {
        if (!is_object($value)) {
            return false;
        }

        //We can not clone enums
        if ($value instanceof \UnitEnum) {
            return false;
        }

        //Otherwise use the cloneable flag from the parameter metadata
        return $parameterMetadata->isCloneable();
    }

    /**
     * Clones the given data if needed and returns the cloned data
     * @param  mixed  $data
     * @param  ParameterMetadata  $parameter
     * @return mixed
     */
    private function cloneDataIfNeeded(mixed $data, ParameterMetadata $parameter): mixed
    {
        if ($this->shouldBeCloned($data, $parameter)) {
            //Check if data is cloneable by PHP and throw an exception if not
            $reflClass = new \ReflectionClass($data);
            if (!$reflClass->isCloneable()) {
                throw new ParameterDataNotCloneableException($parameter, $reflClass);
            }

            return clone $data;
        }

        return $data;
    }
}