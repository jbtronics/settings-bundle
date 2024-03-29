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

use Jbtronics\SettingsBundle\Tests\TestApplication\DataFixtures\AppFixtures;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->parameters()->set('locale', 'en');

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public()
    ;

    //Load AppFixtures
    $services->set(AppFixtures::class)->tag('doctrine.fixture.orm');

    $services->load('Jbtronics\\SettingsBundle\\Tests\\TestApplication\\', '../src/*')
        ->exclude('../src/{Entity,Tests,Kernel.php}');

    $services->load('Jbtronics\\SettingsBundle\\Tests\\TestApplication\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');
};