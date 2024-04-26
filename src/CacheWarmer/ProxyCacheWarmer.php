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


namespace Jbtronics\SettingsBundle\CacheWarmer;

use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * This class creates the proxy classes for the settings classes at cache warmup.
 */
final class ProxyCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly SettingsRegistryInterface $settingsRegistry,
        private readonly ProxyFactoryInterface $proxyFactory,
    ) {

    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $proxyDir = $this->proxyFactory->getProxyCacheDir();
        $files = [];

        //Retrieve all defined settings classes
        $classes = $this->settingsRegistry->getSettingsClasses();
        if (empty($classes)) {
            return [];
        }

        //And generate the proxy classes for them
        $this->proxyFactory->generateProxyClassFiles($classes);

        //And return all generated proxy files for preloading
        foreach (scandir($proxyDir) as $file) {
            if (!is_dir($file = $proxyDir.'/'.$file)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}