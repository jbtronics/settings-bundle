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


namespace Jbtronics\SettingsBundle\Migrations;

use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait PHPValueConverterTrait
{
    protected ?ParameterTypeRegistryInterface $parameterTypeRegistry;

    #[Required()]
    public function setParameterTypeRegistry(ParameterTypeRegistryInterface $parameterTypeRegistry): void
    {
        $this->parameterTypeRegistry = $parameterTypeRegistry;
    }

    /**
     * Extract a certain field from the data array and convert it to a PHP value. The parameter must be defined in the
     * current/latest schema!
     * @param  string  $parameterName The name of the parameter to extract. This is not necessarily the same as the property name!
     * @param  array  $data The data array to extract the value from
     * @param  SettingsMetadata  $metadata The metadata of the settings class to use
     * @param  bool  $throw_on_missing_data If set to true, an exception is thrown if the parameter is not set in the data array. Otherwise, the data value is assumed to be null.
     * @return mixed The PHP representation of the parameter value
     */
    protected function getAsPHPValue(string $parameterName, array $data, SettingsMetadata $metadata, bool $throw_on_missing_data = false): mixed
    {
        if (!$this->parameterTypeRegistry) {
            throw new \RuntimeException('The parameterTypeRegistry must be injected before calling getPHPValue()!!');
        }

        $converter = $this->parameterTypeRegistry->getParameterType($metadata->getParameter($parameterName)->getType());

        //If the parameter is not set and the throw flag was set to true, throw an exception
        if ($throw_on_missing_data && !array_key_exists($parameterName, $data)) {
            throw new \RuntimeException(sprintf('The parameter "%s" is missing in the data array!', $parameterName));
        }

        //Otherwise we just assume the parameter is null
        $parameterValue = $data[$parameterName] ?? null;

        return $converter->convertNormalizedToPHP($parameterValue, $metadata->getParameter($parameterName));
    }

    /**
     * Sets a field in the data array using a PHP value. The parameter must be defined in the current/latest schema!
     * It is automatically converted to a normalized value.
     * The data is passed by reference and modified in-place.
     * @param  string  $parameterName The name of the parameter to set. This is not necessarily the same as the property name!
     * @param  mixed  $value  The PHP value to set
     * @param  array  $data The data array to set the value in (passed by reference)
     * @param  SettingsMetadata  $metadata The metadata of the settings class to use
     * @return array The modified data array. Same as the $data parameter.
     */
    protected function setAsPHPValue(string $parameterName, mixed $value, array &$data,  SettingsMetadata $metadata): array
    {
        if (!$this->parameterTypeRegistry) {
            throw new \RuntimeException('The parameterTypeRegistry must be injected before calling getPHPValue()!!');
        }

        $converter = $this->parameterTypeRegistry->getParameterType($metadata->getParameter($parameterName)->getType());

        $data[$parameterName] = $converter->convertPHPToNormalized($value, $metadata->getParameter($parameterName));

        return $data;
    }

}