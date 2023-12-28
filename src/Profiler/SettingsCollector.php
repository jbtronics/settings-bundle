<?php

namespace Jbtronics\SettingsBundle\Profiler;

use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Schema\SettingsSchema;
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
        $settings_classes = $this->configurationRegistry->getSettingsClasses();
        $schemas = [];
        foreach ($settings_classes as $settings_class) {
            $schemas[$settings_class] = $this->schemaManager->getSchema($settings_class);
        }

        $this->data = [
            'settings_classes' => $this->configurationRegistry->getSettingsClasses(),
            'schemas' => $schemas,
        ];
    }

    public static function getTemplate(): ?string
    {
        return '@Settings/profiler/main.html.twig';
    }

    public function getSettingsClasses(): array
    {
        return $this->data['settings_classes'];
    }

    /**
     * @return array<string, SettingsSchema>
     */
    public function getSettingsSchemas(): array
    {
        return $this->data['schemas'];
    }
}