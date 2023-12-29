<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication;

use Jbtronics\SettingsBundle\SettingsBundle;
use Jbtronics\SettingsBundle\UserConfigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;

final class Kernel extends \Symfony\Component\HttpKernel\Kernel
{

    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SettingsBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }
}