<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 * Copyright (c) 2026 Sviatoslav Vysitskyi
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

namespace Jbtronics\SettingsBundle\Metadata\Driver;

use Jbtronics\SettingsBundle\Metadata\EnvVarMode;
use Jbtronics\SettingsBundle\Settings\EmbeddedSettings;
use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;
use Symfony\Component\Yaml\Yaml;

/**
 * Metadata driver that reads settings metadata from YAML files.
 * This allows defining settings configuration in the infrastructure layer (YAML)
 * while keeping the settings classes clean in the application layer (plain PHP).
 *
 * YAML files are named by replacing namespace separators with dots:
 * e.g. App\Settings\TestSettings -> App.Settings.TestSettings.yaml
 *
 * Note: callable envVarMapper is not supported in YAML configuration.
 * Only class-string mappers (service references) can be used.
 */
final class YamlDriver implements MetadataDriverInterface, CompileTimeMetadataDriverInterface
{
    /** @var array<string, array>|null Parsed YAML config keyed by class name */
    private ?array $classConfigs = null;

    /**
     * @param  string[]  $yamlMappingPaths  Directories containing YAML mapping files
     */
    public function __construct(
        private readonly array $yamlMappingPaths,
    ) {
        if (!class_exists(Yaml::class)) {
            throw new \LogicException('The symfony/yaml package is required to use the YAML metadata driver. Install it with: composer require symfony/yaml');
        }
    }

    public function isSettingsClass(string $className): bool
    {
        $this->ensureInitialized();
        return isset($this->classConfigs[$className]);
    }

    public function loadClassMetadata(string $className): ?Settings
    {
        $this->ensureInitialized();

        if (!isset($this->classConfigs[$className])) {
            return null;
        }

        $config = $this->classConfigs[$className];

        return new Settings(
            name: $config['name'] ?? null,
            storageAdapter: $config['storageAdapter'] ?? null,
            storageAdapterOptions: $config['storageAdapterOptions'] ?? [],
            groups: $config['groups'] ?? null,
            version: isset($config['version']) ? (int) $config['version'] : null,
            migrationService: $config['migrationService'] ?? null,
            dependencyInjectable: $config['dependencyInjectable'] ?? true,
            label: $config['label'] ?? null,
            description: $config['description'] ?? null,
            cacheable: $config['cacheable'] ?? null,
        );
    }

    public function loadParameterMetadata(string $className): array
    {
        $this->ensureInitialized();

        if (!isset($this->classConfigs[$className])) {
            return [];
        }

        $config = $this->classConfigs[$className];
        $parameters = [];

        foreach ($config['parameters'] ?? [] as $propertyName => $paramConfig) {
            $envVarMode = EnvVarMode::INITIAL;
            if (isset($paramConfig['envVarMode'])) {
                $envVarMode = self::resolveEnvVarMode($paramConfig['envVarMode'], $propertyName, $className);
            }

            $parameters[$propertyName] = new SettingsParameter(
                type: $paramConfig['type'] ?? null,
                name: $paramConfig['name'] ?? null,
                label: $paramConfig['label'] ?? null,
                description: $paramConfig['description'] ?? null,
                options: $paramConfig['options'] ?? [],
                formType: $paramConfig['formType'] ?? null,
                formOptions: $paramConfig['formOptions'] ?? [],
                nullable: $paramConfig['nullable'] ?? null,
                groups: $paramConfig['groups'] ?? null,
                envVar: $paramConfig['envVar'] ?? null,
                envVarMode: $envVarMode,
                envVarMapper: $paramConfig['envVarMapper'] ?? null,
                cloneable: $paramConfig['cloneable'] ?? true,
            );
        }

        return $parameters;
    }

    public function loadEmbeddedMetadata(string $className): array
    {
        $this->ensureInitialized();

        if (!isset($this->classConfigs[$className])) {
            return [];
        }

        $config = $this->classConfigs[$className];
        $embeddeds = [];

        foreach ($config['embeddedSettings'] ?? [] as $propertyName => $embedConfig) {
            $embeddeds[$propertyName] = new EmbeddedSettings(
                target: $embedConfig['target'] ?? null,
                groups: $embedConfig['groups'] ?? null,
                label: $embedConfig['label'] ?? null,
                description: $embedConfig['description'] ?? null,
                formOptions: $embedConfig['formOptions'] ?? null,
            );
        }

        return $embeddeds;
    }

    public function getAllManagedClassNames(): array
    {
        $this->ensureInitialized();
        return array_keys($this->classConfigs);
    }

    private function ensureInitialized(): void
    {
        if ($this->classConfigs !== null) {
            return;
        }

        $this->classConfigs = [];

        foreach ($this->yamlMappingPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.yaml') ?: [];
            $ymlFiles = glob($path . '/*.yml') ?: [];
            $files = array_merge($files, $ymlFiles);

            foreach ($files as $file) {
                $this->loadFile($file);
            }
        }
    }

    private static function resolveEnvVarMode(string $value, string $propertyName, string $className): EnvVarMode
    {
        foreach (EnvVarMode::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid envVarMode "%s" for parameter "%s" of class "%s". Valid values are: %s',
            $value,
            $propertyName,
            $className,
            implode(', ', array_map(static fn(EnvVarMode $m) => $m->name, EnvVarMode::cases()))
        ));
    }

    private function loadFile(string $file): void
    {
        $content = Yaml::parseFile($file);

        if (!is_array($content)) {
            return;
        }

        foreach ($content as $className => $config) {
            if (!is_string($className) || !is_array($config)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid YAML settings mapping in file "%s": top-level keys must be fully qualified class names mapping to configuration arrays.',
                    $file
                ));
            }

            if (!class_exists($className)) {
                throw new \InvalidArgumentException(sprintf(
                    'Class "%s" referenced in YAML settings mapping file "%s" does not exist.',
                    $className,
                    $file
                ));
            }

            if (isset($this->classConfigs[$className])) {
                throw new \InvalidArgumentException(sprintf(
                    'Duplicate YAML settings mapping for class "%s" found in file "%s". Each class may only be mapped once.',
                    $className,
                    $file
                ));
            }

            $this->classConfigs[$className] = $config;
        }
    }

    public static function getServiceMetadataForContainerCompilation(array $containerParameters): array
    {
        $yamlMappingPaths = $containerParameters['jbtronics.settings.yaml_mapping_paths'] ?? [];

        if (empty($yamlMappingPaths) || !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            return [];
        }

        // Use the YamlDriver to discover classes at build time
        $yamlDriver = new YamlDriver($yamlMappingPaths);
        $classNames = $yamlDriver->getAllManagedClassNames();

        $output = [];

        foreach ($classNames as $className) {
            $classMetadata = $yamlDriver->loadClassMetadata($className);
            if ($classMetadata === null) {
                continue;
            }

            $output[$className] = $classMetadata;
        }

        return $output;
    }
}
