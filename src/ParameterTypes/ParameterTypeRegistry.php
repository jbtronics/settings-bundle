<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ParameterTypeRegistry implements ParameterTypeRegistryInterface
{

    public function __construct(
        private readonly ServiceLocator $locator
    )
    {

    }

    /**
     * Return an array of all registered parameter types.
     * in the format ['parameter_type_service_name' => 'parameter_type_service_class']
     * @return array
     * @phpstan-return array<string, class-string<ParameterTypeInterface>>
     */
    public function getRegisteredParameterTypes(): array
    {
        return $this->locator->getProvidedServices();
    }

    public function getParameterType(string $className): ParameterTypeInterface
    {
        return $this->locator->get($className);
    }
}