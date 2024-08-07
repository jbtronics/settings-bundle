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

namespace Jbtronics\SettingsBundle\Tests\Storage;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Jbtronics\SettingsBundle\Storage\ORMStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\OtherSettingsEntry;
use Jbtronics\SettingsBundle\Tests\TestApplication\Entity\SettingsEntry;
use LogicException;
use stdClass;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ORMStorageAdapterTest extends KernelTestCase
{
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get('doctrine');
    }

    public function testSaveAndLoadNewEntry(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);

        $adapter->save('foo', ['bar' => 'baz']);

        $this->assertEquals(['bar' => 'baz'], $adapter->load('foo'));
    }

    public function testSaveAndLoadNewEntryOverride(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);

        $adapter->save('foo', ['bar' => 'baz'], ['entity_class' => OtherSettingsEntry::class]);

        $this->assertEquals(['bar' => 'baz'], $adapter->load('foo', ['entity_class' => OtherSettingsEntry::class]));
    }

    public function testLoadNonExisting(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);

        //Non existing key must return null
        $this->assertNull($adapter->load('non_existing'));

        //Test that it also work with overridden entity class
        $this->assertNull($adapter->load('non_existing', ['entity_class' => OtherSettingsEntry::class]));
    }

    public function testLoadExisting(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);
        $this->assertEquals(['foo' => 'existing1'], $adapter->load('existing1'));

        $this->assertEquals(['foo' => 'existing2'], $adapter->load('existing2', ['entity_class' => OtherSettingsEntry::class]));
    }

    public function testFetchAll(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, true);
        $this->assertEquals(['foo' => 'existing1'], $adapter->load('existing1'));

        $adapter = new ORMStorageAdapter($this->managerRegistry, OtherSettingsEntry::class, true);
    }

    public function testThrowOnInvalidDefaultEntityClass(): void
    {
        //Must throw an exception, if the default entity class is not a subclass of AbstractSettingsORMEntry
        $this->expectException(InvalidArgumentException::class);
        new ORMStorageAdapter($this->managerRegistry, stdClass::class, false);
    }

    public function testThrowOnInvalidEntityClass(): void
    {
        //Must throw an exception, if the passed entity class is not a manager entity
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No entity manager found for class/');
        $adapter->load('foo', ['entity_class' => stdClass::class]);
    }

    public function testThrowIfNoEntityClassResolvable(): void
    {
        //Must throw an exception, if no entity class is passed and no default entity class is set
        $adapter = new ORMStorageAdapter($this->managerRegistry, null, false);
        $this->expectException(LogicException::class);
        $adapter->load('foo');
    }

    public function testInvalidEntityManager(): void
    {
        $adapter = new ORMStorageAdapter($this->managerRegistry, SettingsEntry::class, false);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Doctrine ORM Manager named/');

        //With the option, doctrine must try to attempt to load the entity manager with the given name
        $adapter->load('foo', ['entity_manager' => 'non_existing']);
    }

}
