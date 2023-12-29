<?php

use Jbtronics\SettingsBundle\Tests\TestApplication\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->import('../src/Controller/', 'attribute');
};