<?php


/*
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

namespace Jbtronics\SettingsBundle;

use Closure;
use Jbtronics\SettingsBundle\DependencyInjection\JbtronicsSettingsExtension;
use Jbtronics\SettingsBundle\DependencyInjection\ConfigureInjectableSettingsPass;
use Jbtronics\SettingsBundle\Proxy\Autoloader;
use Jbtronics\SettingsBundle\Proxy\ProxyFactoryInterface;
use Jbtronics\SettingsBundle\Settings\Settings;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class JbtronicsSettingsBundle extends AbstractBundle
{

    private ?Closure $autoloader = null;

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new JbtronicsSettingsExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->processSettingsServices($container);

        $container->addCompilerPass(new ConfigureInjectableSettingsPass());
    }

    protected function processSettingsServices(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(Settings::class,
            static function (
                ChildDefinition $definition,
                Settings $attribute,
                \Reflector $reflector
            ): void {
                //If the settings class is dependency injectable, add the injectable settings tag
                if ($attribute->canBeDependencyInjected()) {
                    $definition->addTag(JbtronicsSettingsExtension::TAG_INJECTABLE_SETTINGS);
                } else {
                    //If the settings class is not dependency injectable, remove the injectable settings tag
                    $definition->addTag(ConfigureInjectableSettingsPass::TAG_TO_REMOVE);
                }
            }
        );
    }

    public function boot(): void
    {
        //Register a autoloader to handle the proxy classes. This is important for things like unserializing proxy classes

        $proxyDir = $this->container->getParameter('jbtronics.settings.proxy_dir');
        $proxyNamespace = $this->container->getParameter('jbtronics.settings.proxy_namespace');
        /** @var ProxyFactoryInterface $proxyFactory */
        $proxyFactory = $this->container->get('jbtronics.settings.proxy_factory');

        $proxyGeneratorCallback = static function ($proxyDir, $proxyNamespace, $class) use ($proxyFactory): void {
            $proxyFactory->generateProxyClassFiles([$class]);
        };

        $this->autoloader = Autoloader::register($proxyDir, $proxyNamespace, $proxyGeneratorCallback);
    }

    public function shutdown(): void
    {
        if ($this->autoloader !== null) {
            spl_autoload_unregister($this->autoloader);
            $this->autoloader = null;
        }
    }
}