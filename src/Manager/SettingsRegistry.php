<?php


/*
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

namespace Jbtronics\SettingsBundle\Manager;

use Jbtronics\SettingsBundle\Settings\Settings;
use Spatie\StructureDiscoverer\Discover;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This class is responsible for getting all configuration classes, defined in the application.
 * It scans the files in the defined directories for classes with the #[ConfigClass] attribute.
 */
final class SettingsRegistry implements SettingsRegistryInterface, CacheWarmerInterface
{

    private const CACHE_KEY = 'jbtronics.settings.settings_classes';

    /**
     * @param  array  $directories The directories to scan for configuration classes
     * @param  CacheInterface  $cache The cache to use for caching the configuration classes
     * @param  bool  $debug_mode If true, the cache is ignored and the directories are scanned on every request
     */
    public function __construct(
        private readonly array $directories,
        private readonly CacheInterface $cache,
        private readonly bool $debug_mode,
    )
    {
    }

    public function getSettingsClasses(): array
    {
        if ($this->debug_mode) {
            return $this->searchInPathes($this->directories);
        }

        return $this->cache->get(self::CACHE_KEY, function () {
            return $this->searchInPathes($this->directories);
        });
    }

    /**
     * @param string[]  $pathes
     * @return string[]
     */
    private function searchInPathes(array $pathes): array
    {
        return Discover::in(...$pathes)
            ->withAttribute(Settings::class)
            ->get()
            ;
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        //Call the getter function to warm up the cache
        $this->getSettingsClasses();
        return [];
    }
}