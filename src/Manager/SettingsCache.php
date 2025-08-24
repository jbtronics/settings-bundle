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
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @internal
 */
final class SettingsCache implements SettingsCacheInterface
{
    private const CACHE_KEY_PREFIX = 'jbtronics_settings_';
    private const CACHE_TAG = 'jbtronics_settings_cached_data';

    public function __construct(
        private readonly TagAwareAdapterInterface $cache,
        private readonly int $ttl = 0,
        private readonly bool $invalidateOnEnvChange = true,
    )
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
        $item = $this->getCacheItem($settings);
        $item->set($this->toCacheableRepresentation($settings, $value));
        if (!$this->cache instanceof TagAwareAdapterInterface) {
            throw new \RuntimeException('The cache pool must be tag-aware to use the settings cache.');
        }
        $item->tag(self::CACHE_TAG);
        //Set the TTL if it is greater than 0
        if ($this->ttl > 0) {
            $item->expiresAfter($this->ttl);
        }
        $this->cache->save($item);
    }

    public function invalidateData(SettingsMetadata $settings): void
    {
        $this->cache->deleteItem($this->getCacheKey($settings));
    }

    private function getCacheItem(SettingsMetadata $settings): ItemInterface
    {
        return $this->cache->getItem($this->getCacheKey($settings));
    }

    private function getEnvVarHash(SettingsMetadata $settings): string
    {
        //Only get the part of $_ENV that is relevant for the settings
        $relevantEnvVars = $settings->getCacheAffectingEnvVars();
        if (empty($relevantEnvVars)) {
            return 'noenv';
        }

        $relevantEnvData = array_intersect_key($_ENV, array_flip($relevantEnvVars));
        return substr(sha1(json_encode($relevantEnvData, JSON_THROW_ON_ERROR)), 0, 8);
    }

    private function getCacheKey(SettingsMetadata $settings): string
    {
        //The storage key should be unique enough to avoid conflicts
        $tmp = self::CACHE_KEY_PREFIX . $settings->getStorageKey();

        if ($this->invalidateOnEnvChange) {
            $tmp .= '_' . $this->getEnvVarHash($settings);
        }
        return $tmp;
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

    public function invalidateAll(): void
    {
        $this->cache->invalidateTags([self::CACHE_TAG]);
    }
}