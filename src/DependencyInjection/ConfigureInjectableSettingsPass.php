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

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass registers all settings classes, which are marked as injectable via dependency injection and
 * configures how to retrieve them.
 * It also removes all services marked for removal (which should not be injectable as settings service).
 * @internal
 */
final class ConfigureInjectableSettingsPass implements CompilerPassInterface
{
    public const TAG_TO_REMOVE = 'jbtronics.settings.service_to_remove';

    public function process(ContainerBuilder $container): void
    {
        if (method_exists($container, 'findTaggedResourceIds')) { //Symfony 7.3+
            foreach ($container->findTaggedResourceIds(JbtronicsSettingsExtension::RESSOURCE_TAG_SETTINGS) as $id => $tagAttr) {
                $injectable = $tagAttr['injectable'] ?? true;

                //If the settings is not marked as injectable, skip it
                if (!$injectable) {
                    continue;
                }

                //Otherwise configure it and remove the exclusion tag
                $definition = $container->getDefinition($id);
                $definition->clearTag('container.excluded');
                $this->applyServiceDefinition($definition);
            }
        } else { //Older Symfony versions

            trigger_deprecation('jbtronics/settings-bundle', '3.2', 'Using older symfony versions then 7.3 is deprecated. Please upgrade to benefit from ressource tags.');

            //Register all classes marked as injectable settings
            foreach ($container->findTaggedServiceIds(JbtronicsSettingsExtension::TAG_INJECTABLE_SETTINGS) as $id => $tags) {
                $definition = $container->getDefinition($id);
                $this->applyServiceDefinition($definition);
            }

            //Remove all services marked for removal
            foreach ($container->findTaggedServiceIds(self::TAG_TO_REMOVE) as $id => $tags) {
                $container->removeDefinition($id);
            }
        }
    }

    private function applyServiceDefinition(Definition $definition): void
    {
        $definition->setFactory([new Reference(SettingsManagerInterface::class), 'get']);
        //Instance should be created lazily
        $definition->setArguments([$definition->getClass(), '$lazy' => true]);
    }
}