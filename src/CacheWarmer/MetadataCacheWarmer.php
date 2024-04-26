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

use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * This cache warmer, initializes the settings registry, the metadata cache and the embedded cascade cache.
 */
final class MetadataCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsRegistry $settingsRegistry,
    )
    {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        //Iterate over all settings classes and retrieve the metadata (this also initializes the settings registry)
        foreach ($this->settingsRegistry->getSettingsClasses() as $class) {
            //Initialize the metadata for the class
            $this->metadataManager->getSettingsMetadata($class);

            //Initialize the resolve the embedded cascade for the class
            $this->metadataManager->resolveEmbeddedCascade($class);
        }

        return [];
    }
}