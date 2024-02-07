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

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Controller;

use Jbtronics\SettingsBundle\Form\SettingsFormFactory;
use Jbtronics\SettingsBundle\Form\SettingsFormFactoryInterface;
use Jbtronics\SettingsBundle\Manager\SettingsHydratorInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
use Symfony\Component\Routing\Attribute\Route;

class DummyController
{

    //We need to inject the registry here, to make sure the services dont get optimized away, and we can access
    //them in the tests
    public function __construct(
        private readonly ParameterTypeRegistryInterface $parameterTypeRegistry,
    private readonly StorageAdapterRegistryInterface $storageAdapterRegistry,
    private readonly MetadataManagerInterface $schemaManager,
    private readonly SettingsHydratorInterface $settingsHydrator,
    private readonly SettingsManagerInterface $settingsManager,
    private readonly SettingsFormFactoryInterface $formFactory,
    )
    {
    }

}