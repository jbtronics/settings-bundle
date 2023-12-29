<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ParameterTypeRegistry implements ParameterTypeRegistryInterface
{

    public function __construct(
        private readonly ContainerInterface $locator
    )
    {

    }

    public function getParameterType(string $className): ParameterTypeInterface
    {
        return $this->locator->get($className);
    }
}