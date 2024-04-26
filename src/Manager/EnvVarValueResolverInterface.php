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

use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;

/**
 * This service is responsible for actually resolving/retrieving the value of a parameter based on the configured
 * environment variable.
 * @internal
 */
interface EnvVarValueResolverInterface
{

    /**
     * Retrieves the value of the given parameterMetadata from the environment variable which should be used
     * based on the configuration of the parameterMetadata.
     * The value is processed by the configured mapping function.
     * An exception is thrown if the environment variable is not set, so check with hasValue() before calling this method.
     * @param  ParameterMetadata  $metadata
     * @throws \LogicException if the parameterMetadata has no environment variable set.
     * @throws EnvNotFoundException if the requested environment variable is not set.
     * @return mixed
     */
    public function getValue(ParameterMetadata $metadata): mixed;

    /**
     * Checks if there is a environment variable set for the given parameterMetadata.
     * It returns false, if either the environment variable is not set or the parameterMetadata has no environment variable configured.
     * @param  ParameterMetadata  $metadata
     * @return bool
     */
    public function hasValue(ParameterMetadata $metadata): bool;
}