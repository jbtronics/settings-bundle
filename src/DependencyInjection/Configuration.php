<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('jbtronics_settings');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()

            ->arrayNode('search_paths')
                ->defaultValue(['%kernel.project_dir%/src/Settings/'])
                ->scalarPrototype()->end()
            ->end()

            ->scalarNode('proxy_dir')->defaultValue('%kernel.cache_dir%/jbtronics_settings/proxies')->end()

            ->scalarNode('proxy_namespace')->defaultValue('Jbtronics\SettingsBundle\Proxies')->end()

            ->scalarNode('default_storage_adapter')->defaultNull()->end()

            ->booleanNode('save_after_migration')->defaultTrue()->end()

            ->end();

        $this->addFileStorageConfiguration($rootNode);

        return $treeBuilder;
    }

    private function addFileStorageConfiguration(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('file_storage')
                ->addDefaultsIfNotSet()
                ->children()
                ->scalarNode('storage_directory')->defaultValue('%kernel.project_dir%/var/jbtronics_settings/')->end()
                ->scalarNode('default_filename')->defaultValue('settings')->end()
            ->end();
    }
}