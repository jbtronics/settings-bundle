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

namespace Jbtronics\SettingsBundle\Tests\Schema;

use Jbtronics\SettingsBundle\Schema\SchemaManager;
use Jbtronics\SettingsBundle\Schema\SchemaManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchemaManagerTest extends KernelTestCase
{

    private SchemaManagerInterface $schemaManager;

    public function setUp(): void
    {
        self::bootKernel();
        $this->schemaManager = $this->getContainer()->get(SchemaManagerInterface::class);
    }

    public function testIsConfigClass(): void
    {
        //Basic classes should not be config classes
        $this->assertFalse($this->schemaManager->isSettingsClass(\DateTime::class));
        $this->assertFalse($this->schemaManager->isSettingsClass(\stdClass::class));

        //But our config class should recognize as such
        $this->assertTrue($this->schemaManager->isSettingsClass(SimpleSettings::class));

        //This should also work with short names
        $this->assertTrue($this->schemaManager->isSettingsClass('simple'));
    }

    public function testGetSchemaInvalidClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->schemaManager->getSchema(\DateTime::class);
    }

    public function testGetSchema(): void
    {
        $schema = $this->schemaManager->getSchema(SimpleSettings::class);

        $this->assertEquals(SimpleSettings::class, $schema->getClassName());

        //This should also work with the short name of the class
        $schema2 = $this->schemaManager->getSchema('simple');
        $this->assertSame($schema, $schema2);
    }
}
