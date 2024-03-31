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
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;

class EnvVarValueResolver implements EnvVarValueResolverInterface
{
    public function __construct(
        private readonly \Closure $getEnvClosure,
        private readonly ParameterTypeRegistryInterface $parameterTypeRegistry
    )
    {
    }

    public function getValue(ParameterMetadata $metadata): mixed
    {
        //Ensure that the metadata has an environment variable set
        if ($metadata->getEnvVar() === null) {
            throw new \LogicException(sprintf("The parameter '%s' on class '%s' has no environment variable set.",
                $metadata->getName(), $metadata->getClassName()));
        }

        //Try to resolve the value from the environment variable
        $result = $this->getEnv($metadata->getEnvVar());

        $mapper = $metadata->getEnvVarMapper();

        //If no mapping function is set, return the value as is
        if ($mapper === null) {
            return $result;
        }

        //If the mapping function is a callable, call it
        if (is_callable($mapper)) {
            //Otherwise, apply the mapping function
            return ($metadata->getEnvVarMapper())($result);
        }

        if (is_string($mapper)) {
            //If the mapping function is a string, try to resolve it from the parameter type registry and apply it
            if (is_a($mapper, ParameterTypeInterface::class, true)) {
                return $this->parameterTypeRegistry->getParameterType($mapper)->convertNormalizedToPHP($result, $metadata);
            }

            throw new \LogicException("The string provided as a mapping function must be a class implementing ParameterTypeInterface!");
        }

        throw new \RuntimeException("Unknown mapping function type!");
    }

    public function hasValue(ParameterMetadata $metadata): bool
    {
        //If the metadata has no environment variable set, return false
        if ($metadata->getEnvVar() === null) {
            return false;
        }

        //Check if the environment variable is set
        try {
            $this->getEnv($metadata->getEnvVar());
        } catch (\Exception $e) {
            return false;
        }

        //If we reach this point, the environment variable is set
        return true;
    }


    /**
     * Get the value of an environment variable. It is possible to use every kind of environment processor available
     * in Symfony.
     * @param  string  $env
     * @return mixed
     */
    protected function getEnv(string $env): mixed
    {
        return ($this->getEnvClosure)($env);
    }
}