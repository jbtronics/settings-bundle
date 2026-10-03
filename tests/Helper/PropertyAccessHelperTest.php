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


namespace Jbtronics\SettingsBundle\Tests\Helper;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use PHPUnit\Framework\TestCase;

class PropertyAccessHelperTestParent
{
    private string $parentPrivate = 'parent';
}

class PropertyAccessHelperTestChild extends PropertyAccessHelperTestParent
{
    private static int $static = 1;
    protected int $protected = 2;
}

class PropertyAccessHelperTest extends TestCase
{
    public function testGetSetOwnProperty(): void
    {
        $object = new PropertyAccessHelperTestChild();
        $this->assertSame(2, PropertyAccessHelper::getProperty($object, 'protected'));
        PropertyAccessHelper::setProperty($object, 'protected', 5);
        $this->assertSame(5, PropertyAccessHelper::getProperty($object, 'protected'));
    }

    public function testGetSetParentPrivateProperty(): void
    {
        $object = new PropertyAccessHelperTestChild();
        $this->assertSame('parent', PropertyAccessHelper::getProperty($object, 'parentPrivate'));
        PropertyAccessHelper::setProperty($object, 'parentPrivate', 'changed');
        $this->assertSame('changed', PropertyAccessHelper::getProperty($object, 'parentPrivate'));

        //The values of different instances must not affect each other, even if the reflection property is cached
        $this->assertSame('parent', PropertyAccessHelper::getProperty(new PropertyAccessHelperTestChild(), 'parentPrivate'));
    }

    public function testGetSetStaticProperty(): void
    {
        //Access via the object and via the class name must work for static properties
        PropertyAccessHelper::setProperty(new PropertyAccessHelperTestChild(), 'static', 10);
        $this->assertSame(10, PropertyAccessHelper::getProperty(PropertyAccessHelperTestChild::class, 'static'));

        PropertyAccessHelper::setProperty(PropertyAccessHelperTestChild::class, 'static', 1);
        $this->assertSame(1, PropertyAccessHelper::getProperty(new PropertyAccessHelperTestChild(), 'static'));
    }

    public function testReflectionPropertyIsCached(): void
    {
        $object = new PropertyAccessHelperTestChild();
        $this->assertSame(
            PropertyAccessHelper::getAccessibleReflectionProperty($object, 'protected'),
            PropertyAccessHelper::getAccessibleReflectionProperty(PropertyAccessHelperTestChild::class, 'protected')
        );
    }

    public function testNonExistingPropertyThrows(): void
    {
        $this->expectException(\LogicException::class);
        PropertyAccessHelper::getProperty(new PropertyAccessHelperTestChild(), 'doesNotExist');
    }
}
