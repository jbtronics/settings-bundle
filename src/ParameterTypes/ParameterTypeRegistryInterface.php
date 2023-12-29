<?php

namespace Jbtronics\SettingsBundle\ParameterTypes;

/**
 * The registry for
 */
interface ParameterTypeRegistryInterface
{
    /**
     * Returns the parameter type service for the given class name.
     * @template T of ParameterTypeInterface
     * @param  string  $className
     * @phpstan-param class-string<T> $className
     * @return ParameterTypeInterface
     * @phpstan-return T
     */
    public function getParameterType(string $className): ParameterTypeInterface;

    /**
     * Return an array of all registered parameter types.
     * in the format ['parameter_type_service_name' => 'parameter_type_service_class']
     * @return array
     * @phpstan-return array<string, class-string<ParameterTypeInterface>>
     */
    public function getRegisteredParameterTypes(): array;
}