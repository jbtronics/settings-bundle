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


$ormConfig = [
    'auto_generate_proxy_classes' => true,
    'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
    'auto_mapping' => true,
    'mappings' => [
        'TestEntities' => [
            'is_bundle' => false,
            'type' => 'attribute',
            'dir' => '%kernel.project_dir%/src/Entity',
            'prefix' => 'Jbtronics\SettingsBundle\Tests\TestApplication\Entity',
            'alias' => 'app',
        ],
    ],
];

// Doctrine ORM supports native lazy objects on PHP 8.4+ via config.
if (PHP_VERSION_ID >= 80400) {
    $ormConfig['enable_native_lazy_objects'] = true;
}

$container->loadFromExtension('doctrine', [
    'dbal' => [
        'driver' => 'pdo_sqlite',
        'path' => '%kernel.cache_dir%/test_database.sqlite',
    ],
    'orm' => $ormConfig,
]);
