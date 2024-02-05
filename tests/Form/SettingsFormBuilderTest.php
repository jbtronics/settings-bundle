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

use Jbtronics\SettingsBundle\Form\SettingsFormBuilder;
use Jbtronics\SettingsBundle\Form\SettingsFormBuilderInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\ParameterMetadata;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use PHPStan\Type\IntegerType;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints\Valid;

class SettingsFormBuilderTest extends KernelTestCase
{

    private SettingsFormBuilder $service;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsFormBuilderInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    public function testAddSettingsParameter(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test', type: StringType::class, nullable: false, formType: NumberType::class);

        //Ensure that the form builder is called with the correct arguments
        $builder->expects($this->once())->method('add')->with('test', NumberType::class, ['label' => 'test', 'help' => null, 'required' => true]);

        $this->service->addSettingsParameter($builder, $parameter);
    }

    public function testAddSettingsFormWithAllParameters(): void
    {
        $parameterMetadata = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true, groups: ['group1']),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, name: 'name2', groups: ['group1', 'group2']),
            new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: true, name: 'name3',label:  'label3', description: 'description3', groups: ['group2', 'group3']),
        ];

        $schema =  new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            defaultGroups: ['group1', 'group2'],
        );

        $builder = $this->formFactory->createBuilder();
        $this->service->buildSettingsForm($builder, $schema);

        //We should now have 3 sub elements in the form builder
        $this->assertCount(3, $builder);

        //All parameters should be in the form builder
        $this->assertTrue($builder->has('property1'));
        $this->assertTrue($builder->has('property2'));
        $this->assertTrue($builder->has('property3'));
    }

    public function testBuildSettingsFormForGroups(): void
    {
        $parameterMetadata = [
            new ParameterMetadata(self::class, 'property1', IntType::class, nullable: true, groups: ['group1']),
            new ParameterMetadata(self::class, 'property2', StringType::class, nullable: true, name: 'name2', groups: ['group1', 'group2']),
            new ParameterMetadata(self::class, 'property3', BoolType::class, nullable: true, name: 'name3',label:  'label3', description: 'description3', groups: ['group2', 'group3']),
        ];

        $schema =  new SettingsMetadata(
            className: self::class,
            parameterMetadata:  $parameterMetadata,
            storageAdapter: InMemoryStorageAdapter::class,
            name: 'test',
            defaultGroups: ['group1', 'group2'],
        );

        $builder = $this->formFactory->createBuilder();
        $this->service->buildSettingsForm($builder, $schema, groups: ['group2', 'group3']);

        //We should now have 2 sub elements in the form builder
        $this->assertCount(2, $builder);

        //Only the parameters with the groups should be in the form builder
        $this->assertFalse($builder->has('property1'));
        $this->assertTrue($builder->has('property2'));
        $this->assertTrue($builder->has('property3'));
    }

    public function testAddEmbeddedSettingsSubForm(): void
    {
        $builder = $this->formFactory->createBuilder();
        /** @var MetadataManagerInterface $metadataManager */
        $metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
        $metadata = $metadataManager->getSettingsMetadata(EmbedSettings::class);
        $embedded = $metadata->getEmbeddedSettings()['simpleSettings'];

        $subBuilder = $this->service->addEmbeddedSettingsSubForm($builder, $embedded);
        //Afterwards the builder should have a sub form with the name of the embedded settings
        $this->assertTrue($builder->has('simpleSettings'));
        //And this form contains the fields of the embedded settings
        $this->assertTrue($builder->get('simpleSettings')->has('value1'));
        $this->assertTrue($builder->get('simpleSettings')->has('value2'));
        $this->assertTrue($builder->get('simpleSettings')->has('value3'));

        //The returned value must be the sub form builder
        $this->assertSame($builder->get('simpleSettings'), $subBuilder);

        //The sun form must have a valid constraint
        $constraints = $builder->get('simpleSettings')->getOption('constraints');
        $this->assertInstanceOf(Valid::class, $constraints[0]);
    }

    public function testAddEmbeddedSettingsSubFormWithGroups(): void
    {
        $builder = $this->formFactory->createBuilder();
        /** @var MetadataManagerInterface $metadataManager */
        $metadataManager = self::getContainer()->get(MetadataManagerInterface::class);
        $metadata = $metadataManager->getSettingsMetadata(EmbedSettings::class);

        $embedded = $metadata->getEmbeddedSettings()['circularSettings'];
        //This runs only when the group restriction is considered, otherwise an infinite loop is created
        $this->service->addEmbeddedSettingsSubForm($builder, $embedded, groups: ['default']);

        //Afterwards the builder should have a sub form with the name of the embedded settings
        $this->assertTrue($builder->has('circularSettings'));

        //And this form contains the fields of the embedded settings
        $this->assertTrue($builder->get('circularSettings')->has('bool'));
        //And also embedded settings
        $this->assertTrue($builder->get('circularSettings')->has('simpleSettings'));
    }

    public function testGetFormTypeForParameter(): void
    {
        //Check for explicitly given form type
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test', type: StringType::class, nullable: false, formType: NumberType::class);
        $this->assertEquals(NumberType::class, $this->service->getFormTypeForParameter($parameter));

        //Check for the default form type
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test', type: StringType::class, nullable: false);
        $this->assertEquals(TextType::class, $this->service->getFormTypeForParameter($parameter));
    }

    public function testGetFormOptions(): void
    {
        //Check for empty options
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test',
            type: StringType::class, nullable: false, name: 'Name', description: 'Description');
        $this->assertEquals(['label' => 'Name', 'help' => 'Description', 'required' => true], $this->service->getFormOptions($parameter));

        //For a nullable parameter, the required option should be set to false by default
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test',
            type: StringType::class, nullable: true, name: 'Name', description: 'Description');
        $this->assertEquals(['label' => 'Name', 'help' => 'Description', 'required' => false], $this->service->getFormOptions($parameter));

        //Test for overriding options in the parameter type
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test',
            type: BoolType::class, nullable: false, name: 'Name', description: 'Description');
        //The checkbox should not be required
        $this->assertEquals(['label' => 'Name', 'help' => 'Description', 'required' => false], $this->service->getFormOptions($parameter));

        //Test for overriding options in the schema
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test',
            type: StringType::class, nullable: false, name: 'Name', description: 'Description', formOptions: ['required' => false, 'test' => 'test']);

        //The text field should not be required
        $this->assertEquals(['label' => 'Name', 'help' => 'Description', 'required' => false, 'test' => 'test'], $this->service->getFormOptions($parameter));

        //Test for overriding by giving options to the builder
        $parameter = new ParameterMetadata(className: SimpleSettings::class, propertyName: 'test',
            type: StringType::class, nullable: false, name: 'Name', description: 'Description', formOptions: ['required' => false, 'test' => 'test']);

        //The text field should not be required
        $this->assertEquals(['label' => 'Name', 'help' => 'Description', 'required' => true, 'test' => 'other'], $this->service->getFormOptions($parameter, ['required' => true, 'test' => 'other']));
    }
}
