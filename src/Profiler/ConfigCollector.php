<?php

namespace Jbtronics\UserConfigBundle\Profiler;

use Jbtronics\UserConfigBundle\Manager\ConfigurationRegistryInterface;
use Jbtronics\UserConfigBundle\Schema\SchemaManagerInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly ConfigurationRegistryInterface $configurationRegistry,
        private readonly SchemaManagerInterface $schemaManager,
    )
    {

    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $this->data = [
            'config_classes' => $this->configurationRegistry->getConfigClasses(),
        ];
    }
}