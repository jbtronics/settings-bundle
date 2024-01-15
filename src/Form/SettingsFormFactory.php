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


namespace Jbtronics\SettingsBundle\Form;

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class SettingsFormFactory implements SettingsFormFactoryInterface
{
    public function __construct(
        private readonly SettingsManagerInterface $settingsManager,
        private readonly MetadataManagerInterface $metadataManager,
        private readonly SettingsFormBuilderInterface $settingsFormBuilder,
        private readonly FormFactoryInterface $formFactory,
    ) {
    }

    public function createSettingsForm(string $settingsName): FormInterface
    {
        return $this->createSettingsFormBuilder()->getForm();
    }

    public function createSettingsFormBuilder(string $settingsName): FormBuilderInterface
    {
        $settingsMetadata = $this->metadataManager->getSettingsMetadata($settingsName);
        $formBuilder = $this->formFactory->createBuilder(data: $this->settingsManager->get($settingsName));
        $this->settingsFormBuilder->buildSettingsForm($formBuilder, $settingsMetadata);

        return $formBuilder;
    }
}