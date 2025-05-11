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

namespace Jbtronics\SettingsBundle\Tests\Form;

use Jbtronics\SettingsBundle\Form\SettingsFormFactory;
use Jbtronics\SettingsBundle\Form\SettingsFormFactoryInterface;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\ValidatableSettings;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class SettingsFormFactoryTest extends KernelTestCase
{

    private SettingsFormFactory $service;
    private SettingsManagerInterface $settingsManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsFormFactoryInterface::class);
        $this->settingsManager = self::getContainer()->get(SettingsManagerInterface::class);
    }

    public function testCheckForCircularEmbedded(): void
    {
        /** @var MetadataManagerInterface $metadataManager */
        $metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
        $metadata = $metadataManager->getSettingsMetadata(EmbedSettings::class);

        //Without any group restrictions, the thing is ciruclar
        $this->assertTrue($this->service->checkForCircularEmbedded($metadata, null));

        //If we restrict the groups, it should not be circular
        $this->assertFalse($this->service->checkForCircularEmbedded($metadata, ['group1', 'test']));

        //For a purely linear structure, it should not be circular
        $this->assertFalse($this->service->checkForCircularEmbedded($metadataManager->getSettingsMetadata(SimpleSettings::class)));
    }

    public function testCreateSettingsFormBuilder(): void
    {
        $settings = $this->settingsManager->createTemporaryCopy(SimpleSettings::class);

        $formBuilder = $this->service->createSettingsFormBuilder($settings);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        $this->assertSame($settings, $formBuilder->getData());

        //It should contain the 3 parameter fields
        $this->assertCount(3, $formBuilder);

        //It should also work fine for the non-circular embedded settings
        $embed = $this->settingsManager->createTemporaryCopy(EmbedSettings::class);
        $formBuilder = $this->service->createSettingsFormBuilder($embed, ['group1', 'test']);
        $this->assertSame($embed, $formBuilder->getData());
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //With this group restriction, only the embedded settings is on this level
        $this->assertCount(1, $formBuilder);
    }

    public function testCreateSettingsFormBuilderDeprecated(): void
    {
        $formBuilder = $this->service->createSettingsFormBuilder(SimpleSettings::class);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);

        //If a string is passed, the instance associated with the form is the one from the settings manager
        $this->assertSame($this->settingsManager->get(SimpleSettings::class), $formBuilder->getData());

        //It should contain the 3 parameter fields
        $this->assertCount(3, $formBuilder);

        //It should also work fine for the non-circular embedded settings
        $formBuilder = $this->service->createSettingsFormBuilder(EmbedSettings::class, ['group1', 'test']);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //With this group restriction, only the embedded settings is on this level
        $this->assertCount(1, $formBuilder);
    }

    public function testCreateSettingsFormBuilderCircular(): void
    {
        $settings = $this->settingsManager->createTemporaryCopy(EmbedSettings::class);

        //If we encounter a circular embedded settings structure, we should throw an exception
        $this->expectException(LogicException::class);
        $this->service->createSettingsFormBuilder($settings);
    }

    public function testCreateSettingsFormBuilderCircularDeprecated(): void
    {
        //If we encounter a circular embedded settings structure, we should throw an exception
        $this->expectException(LogicException::class);
        $this->service->createSettingsFormBuilder(EmbedSettings::class);
    }

    public function testCreateMultiSettingsFormBuilder(): void
    {
        $array = [$this->settingsManager->createTemporaryCopy(SimpleSettings::class), $this->settingsManager->createTemporaryCopy(ValidatableSettings::class)];

        $formBuilder = $this->service->createMultiSettingsFormBuilder($array);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //The form builder should contain the 2 sub forms
        $this->assertCount(2, $formBuilder);
        //The first form should be set to the first settings instance
        $this->assertSame($array[0], $formBuilder->get("simple")->getData());
        //The second form should be set to the second settings instance
        $this->assertSame($array[1], $formBuilder->get("test234")->getData());

        //It should also work fine for the non-circular embedded settings
        $formBuilder = $this->service->createMultiSettingsFormBuilder($array, ['group1', 'test']);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //With this group restriction, only the embedded settings is on this level
        $this->assertCount(2, $formBuilder);
    }

    public function testCreateMultiSettingsFormBuilderRootSettings(): void
    {
        $array = [$this->settingsManager->createTemporaryCopy(SimpleSettings::class), $this->settingsManager->createTemporaryCopy(ValidatableSettings::class)];

        $formBuilder = $this->service->createMultiSettingsFormBuilder($array, rootFormOptions: ['label' => 'test'] );

        $this->assertSame('test', $formBuilder->getOption('label'));
    }

    public function testCreateMultiSettingsFormBuilderDeprecated(): void
    {
        $formBuilder = $this->service->createMultiSettingsFormBuilder([SimpleSettings::class, ValidatableSettings::class]);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //The form builder should contain the 2 sub forms
        $this->assertCount(2, $formBuilder);

        //It should also work fine for the non-circular embedded settings
        $formBuilder = $this->service->createMultiSettingsFormBuilder([SimpleSettings::class, EmbedSettings::class], ['group1', 'test']);
        $this->assertInstanceOf(FormBuilderInterface::class, $formBuilder);
        //With this group restriction, only the embedded settings is on this level
        $this->assertCount(2, $formBuilder);
    }

    public function testCreateMultiSettingsFormBuilderCircular(): void
    {
        $array = [$this->settingsManager->createTemporaryCopy(SimpleSettings::class), $this->settingsManager->createTemporaryCopy(EmbedSettings::class)];

        //If we encounter a circular embedded settings structure, we should throw an exception
        $this->expectException(LogicException::class);
        $this->service->createMultiSettingsFormBuilder($array );
    }

    public function testCreateMultiSettingsFormBuilderCircularDeprecated(): void
    {
        //If we encounter a circular embedded settings structure, we should throw an exception
        $this->expectException(LogicException::class);
        $this->service->createMultiSettingsFormBuilder([SimpleSettings::class, EmbedSettings::class]);
    }
}
