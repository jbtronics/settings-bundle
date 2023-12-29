<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Controller;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\Storage\StorageAdapterRegistryInterface;
use Symfony\Component\Routing\Attribute\Route;

class DummyController
{

    //We need to inject the registry here, to make sure the services dont get optimized away, and we can access
    //them in the tests
    public function __construct(private readonly ParameterTypeRegistryInterface $parameterTypeRegistry,
    private readonly StorageAdapterRegistryInterface $storageAdapterRegistry)
    {
    }

}