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

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Tests\Profiler;

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Profiler\SettingsCollector;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SettingsCollectorTest extends KernelTestCase
{
    private SettingsCollector $collector;
    private SettingsManagerInterface $settingsManager;
    private SettingsRegistryInterface $settingsRegistry;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->settingsManager = self::getContainer()->get(SettingsManagerInterface::class);
        $this->settingsRegistry = self::getContainer()->get(SettingsRegistryInterface::class);

        $this->collector = new SettingsCollector(
            $this->settingsRegistry,
            self::getContainer()->get(MetadataManagerInterface::class),
            $this->settingsManager,
        );
    }

    public function testGetTemplate(): void
    {
        $this->assertSame('@JbtronicsSettings/profiler/main.html.twig', SettingsCollector::getTemplate());
    }

    public function testCollectSettingsClasses(): void
    {
        $this->collector->collect(new Request(), new Response());

        $this->assertSame($this->settingsRegistry->getSettingsClasses(), $this->collector->getSettingsClasses());
        $this->assertContains(SimpleSettings::class, $this->collector->getSettingsClasses());
    }

    public function testCollectMetadata(): void
    {
        $this->collector->collect(new Request(), new Response());

        $metadata = $this->collector->getSettingsMetadata();
        $this->assertSameSize($this->settingsRegistry->getSettingsClasses(), $metadata);

        foreach ($this->settingsRegistry->getSettingsClasses() as $class) {
            $this->assertArrayHasKey($class, $metadata);
            $this->assertInstanceOf(SettingsMetadata::class, $metadata[$class]);
            $this->assertSame($class, $metadata[$class]->getClassName());
        }
    }

    public function testGetSettingsInstance(): void
    {
        $this->collector->collect(new Request(), new Response());

        $instance = $this->collector->getSettingsInstance(SimpleSettings::class);
        $this->assertInstanceOf(SimpleSettings::class, $instance);
        $this->assertSame($this->settingsManager->get(SimpleSettings::class), $instance);
    }

    public function testGetSettingsParameterValue(): void
    {
        $settings = $this->settingsManager->get(SimpleSettings::class);
        $settings->setValue1('changed');
        $settings->setValue2(42);

        $this->collector->collect(new Request(), new Response());

        $this->assertSame('changed', $this->collector->getSettingsParameterValue(SimpleSettings::class, 'value1'));
        $this->assertSame(42, $this->collector->getSettingsParameterValue(SimpleSettings::class, 'value2'));
        $this->assertFalse($this->collector->getSettingsParameterValue(SimpleSettings::class, 'value3'));
    }

    public function testGetSettingsParameterValueOfEmbeddedSettings(): void
    {
        $this->collector->collect(new Request(), new Response());

        //Embedded settings are lazy loaded, but must be retrievable via the collector
        $value = $this->collector->getSettingsParameterValue(EmbedSettings::class, 'simpleSettings');
        $this->assertInstanceOf(SimpleSettings::class, $value);
        $this->assertSame($this->settingsManager->get(SimpleSettings::class), $value);
    }

    public function testCollectorIsSerializable(): void
    {
        //The profiler stores the collector serialized, so it must survive a serialization roundtrip
        $this->collector->collect(new Request(), new Response());

        /** @var SettingsCollector $unserialized */
        $unserialized = unserialize(serialize($this->collector));

        $this->assertSame($this->collector->getSettingsClasses(), $unserialized->getSettingsClasses());
        $this->assertEquals(array_keys($this->collector->getSettingsMetadata()), array_keys($unserialized->getSettingsMetadata()));
    }
}
