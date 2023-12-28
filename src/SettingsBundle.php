<?php

namespace Jbtronics\SettingsBundle;

use Jbtronics\SettingsBundle\DependencyInjection\SettingsExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SettingsBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SettingsExtension();
    }
}