<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication\Controller;

use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Symfony\Component\Routing\Attribute\Route;

class DummyController
{
    public function __construct(private readonly ParameterTypeRegistryInterface $parameterTypeRegistry)
    {
    }

}