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
use Jbtronics\SettingsBundle\Settings\DependencyInjectableSettings;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * This compiler pass registers all settings classes, which are marked as injectable via dependency injection and
 * configures how to retrieve them.
 * It also removes all services marked for removal (which should not be injectable as settings service).
 */
class ConfigureInjectableSettingsPass implements CompilerPassInterface
{
    public const TAG_TO_REMOVE = 'jbtronics.settings.service_to_remove';

    public function process(ContainerBuilder $container): void
    {
        //Register all classes marked as injectable settings
        foreach ($container->findTaggedServiceIds(JbtronicsSettingsExtension::TAG_INJECTABLE_SETTINGS) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->setFactory([new Reference(SettingsManagerInterface::class), 'get']);
            //Instance should be created lazily
            $definition->setArguments([$definition->getClass(), '$lazy' => true]);
        }

        //Remove all services marked for removal
        foreach ($container->findTaggedServiceIds(self::TAG_TO_REMOVE) as $id => $tags) {
            $container->removeDefinition($id);
        }
    }
}