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


namespace Jbtronics\SettingsBundle\Proxy;

/**
 * @internal
 */
interface ProxyFactoryInterface
{
    /**
     * Returns the directory, where the proxy classes are stored, or null if no proxies classes need to be cached
     * (e.g. when using PHP 8.4 native objects)
     */
    public function getProxyCacheDir(): ?string;

    /**
     * Generates the proxy classes for the given metadata.
     * @param  string[]  $classes
     * @phpstan-param array<class-string> $classes
     * @return void
     * @throws \ReflectionException
     */
    public function generateProxyClassFiles(array $classes): void;

    /**
     * Creates a new proxy instance for the given settings class and initializer.
     * @template T of object
     * @param  string $class
     * @phpstan-param class-string<T> $class
     * @param  \Closure  $initializer
     * @return object
     * @phpstan-return T
     * @throws \ReflectionException
     */
    public function createProxy(string $class, \Closure $initializer): object;
}