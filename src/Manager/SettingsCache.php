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

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @internal
 */
final class SettingsCache implements SettingsCacheInterface
{
    private const CACHE_KEY_PREFIX = 'jbtronics_settings_';

    public function __construct(private readonly AdapterInterface $cache)
    {
    }

    public function hasData(SettingsMetadata $metadata): bool
    {
        return $this->cache->getItem($this->getCacheKey($metadata))->isHit();
    }

    public function applyData(SettingsMetadata $metadata, object $data): object
    {
        //Retrieve the cacheable data
        $cachedData = $this->getCacheItem($metadata)->get();
        if (!is_array($cachedData)) {
            if($cachedData === null) {
                throw new \RuntimeException('No data found in cache for ' . $metadata->getClassName());
            }

            throw new \RuntimeException('Invalid data found in cache for ' . $metadata->getClassName());
        }

        return $this->applyCacheableRepresentation($metadata, $data, $cachedData);
    }

    public function setData(SettingsMetadata $settings, object $value): void
    {
        $item = $this->getCacheItem($settings)->set($this->toCacheableRepresentation($settings, $value));
        $this->cache->save($item);
    }

    public function invalidateData(SettingsMetadata $settings): void
    {
        $this->cache->deleteItem($this->getCacheKey($settings));
    }

    private function getCacheItem(SettingsMetadata $settings): CacheItemInterface
    {
        return $this->cache->getItem($this->getCacheKey($settings));
    }

    private function getCacheKey(SettingsMetadata $settings): string
    {
        //Replace unsafe characters with underscores
        return self::CACHE_KEY_PREFIX . str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $settings->getClassName());
    }

    /**
     * Gets a cacheable representation of the settings object
     * @param  SettingsMetadata  $metadata
     * @param  object  $data
     * @return array
     */
    private function toCacheableRepresentation(SettingsMetadata $metadata, object $data): array
    {
        //Iterate over all settings parameters and convert them to an array
        $result = [];
        foreach ($metadata->getParameters() as $parameterMetadata) {
            $property = $parameterMetadata->getPropertyName();
            $result[$property] = PropertyAccessHelper::getProperty($data, $property);
        }

        return $result;
    }

    private function applyCacheableRepresentation(SettingsMetadata $metadata, object $data, array $cacheableData): object
    {
        //Iterate over all settings parameters and set the values from the cacheable data
        foreach ($metadata->getParameters() as $parameterMetadata) {
            $property = $parameterMetadata->getPropertyName();
            PropertyAccessHelper::setProperty($data, $property, $cacheableData[$property]);
        }

        return $data;
    }
}