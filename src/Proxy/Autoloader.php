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

use InvalidArgumentException;

/**
 * Implements an autoloader for settings proxy classes.
 * This class is based on the class with the same name from the Doctrine ORM:
 * https://github.com/doctrine/common/blob/3.4.x/src/Proxy/Autoloader.php
 * @internal
 */
final class Autoloader
{

    /**
     * Resolves proxy class name to a filename based on the following pattern.
     *
     * 1. Remove Proxy namespace from class name.
     * 2. Remove namespace separators from remaining class name.
     * 3. Return PHP filename from proxy-dir with the result from 2.
     *
     * @param string $proxyDir
     * @param string $proxyNamespace
     * @param string $className
     * @psalm-param class-string $className
     *
     * @return string
     *
     */
    public static function resolveFile(string $proxyDir, string $proxyNamespace, string $className): string
    {
        if (!str_starts_with($className, $proxyNamespace)) {
            throw new \InvalidArgumentException(sprintf(
                'The class "%s" is not part of the proxy namespace "%s".', $className, $proxyNamespace));
        }

        // remove proxy namespace from class name
        $classNameRelativeToProxyNamespace = substr($className, strlen($proxyNamespace));

        // remove namespace separators from remaining class name
        $fileName = str_replace('\\', '', $classNameRelativeToProxyNamespace);

        return $proxyDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    }

    /**
     * Registers and returns autoloader callback for the given proxy dir and namespace.
     *
     * @param  string  $proxyDir
     * @param  string  $proxyNamespace
     * @param  \Closure|null  $notFoundCallback  Invoked when the proxy file is not found.
     *
     * @return \Closure
     *
     */
    public static function register(string $proxyDir, string $proxyNamespace, ?\Closure $notFoundCallback = null): \Closure
    {
        $proxyNamespace = ltrim($proxyNamespace, '\\');

        if ($notFoundCallback !== null && ! is_callable($notFoundCallback)) {
            throw new InvalidArgumentException('The notFoundCallback must be a valid callback or null.');
        }

        $autoloader = static function ($className) use ($proxyDir, $proxyNamespace, $notFoundCallback) {
            if ($proxyNamespace === '') {
                return;
            }

            if (!str_starts_with($className, $proxyNamespace)) {
                return;
            }

            $file = self::resolveFile($proxyDir, $proxyNamespace, $className);

            if ($notFoundCallback && ! file_exists($file)) {
                $notFoundCallback($proxyDir, $proxyNamespace, $className);
            }

            require $file;
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}