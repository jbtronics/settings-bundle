<?php

namespace Jbtronics\SettingsBundle\Tests\TestApplication;

use Jbtronics\SettingsBundle\UserConfigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

final class Kernel extends \Symfony\Component\HttpKernel\Kernel
{

    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        yield new UserConfigBundle();
    }
}