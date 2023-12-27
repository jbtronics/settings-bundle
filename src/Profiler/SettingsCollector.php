<?php

namespace Jbtronics\SettingsBundle\Profiler;

use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly SettingsRegistryInterface $configurationRegistry,
        private readonly SchemaManagerInterface $schemaManager,
    )
    {

    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        $this->data = [
            'settings_classes' => $this->configurationRegistry->getSettingsClasses(),
        ];
    }
}