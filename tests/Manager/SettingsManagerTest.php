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

namespace Jbtronics\SettingsBundle\Tests\Manager;

use Jbtronics\SettingsBundle\Helper\PropertyAccessHelper;
use Jbtronics\SettingsBundle\Manager\SettingsManager;
use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Proxy\SettingsProxyInterface;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\CircularEmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EmbedSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\EnvVarSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\SimpleSettings;
use Jbtronics\SettingsBundle\Tests\TestApplication\Settings\ValidatableSettings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\DataCollector\ValidatorDataCollector;
use Symfony\Component\VarExporter\LazyObjectInterface;

/**
 * The functional/integration test for the SettingsManager
 */
class SettingsManagerTest extends KernelTestCase
{

    /** @var SettingsManager $service */
    private SettingsManagerInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(SettingsManagerInterface::class);
    }

    public function testGet(): void
    {
        //Test if we can get the settings class by classname
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $this->assertInstanceOf(SimpleSettings::class, $settings);

        //Try to change a value
        $settings->setValue1('changed');

        //Test if we can get the settings class by short name
        $settings2 = $this->service->get('simple');
        $this->assertInstanceOf(SimpleSettings::class, $settings2);
        //Must be the same instance of the class
        $this->assertSame($settings, $settings2);

        //Test if the value is changed
        $this->assertEquals('changed', $settings2->getValue1());
    }

    public function testResetToDefaultValues(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $settings->setValue1('changed');

        $this->service->resetToDefaultValues($settings);

        $this->assertEquals('default', $settings->getValue1());
    }

    public function testSaveAndReload(): void
    {
        /** @var SimpleSettings $settings */
        $settings = $this->service->get(SimpleSettings::class);
        $settings->setValue1('changed');

        //Save the settings
        $this->service->save($settings);

        //Save all must also work flawlessly
        $this->service->save();

        //Change the value again
        $settings->setValue1('changed again');

        //Reload the settings
        $this->service->reload($settings);

        //And the value should be the one, which we saved before
        $this->assertEquals('changed', $settings->getValue1());
    }

    public function testGetLazy(): void
    {
        $settings = $this->service->get(SimpleSettings::class, true);
        $this->assertInstanceOf(SimpleSettings::class, $settings);
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings);

        //Test if we can read the value
        $this->assertEquals('default', $settings->getValue1());

        //Test if we can change the value
        $settings->setValue1('changed');
        $this->assertEquals('changed', $settings->getValue1());

        //Test if we can save the settings
        $this->service->save($settings);
    }

    public function testReloadLazy(): void
    {
        $settings = $this->service->get(SimpleSettings::class, true);
        $this->assertInstanceOf(SimpleSettings::class, $settings);
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings);

        //Change the value of the settings
        $settings->setValue1('changed');
        $this->assertEquals('changed', $settings->getValue1());

        //Reloading the settings must work
        $this->service->reload($settings);

        //The value must be the default value again
        $this->assertEquals('default', $settings->getValue1());
    }

    public function testGetEmbedded(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $this->assertInstanceOf(EmbedSettings::class, $settings);

        $this->assertInstanceOf(SimpleSettings::class, $settings->simpleSettings);
        //Should be a lazy loaded instance
        $this->assertInstanceOf(SettingsProxyInterface::class, $settings->simpleSettings);
        if ($settings->simpleSettings instanceof LazyObjectInterface) {
            $this->assertFalse($settings->simpleSettings->isLazyObjectInitialized());
        }

        //The embedded settings should be identical to the ones we get via the settings manager
        $this->assertSame($settings->simpleSettings, $this->service->get(SimpleSettings::class));

        //Test if we can retrieve the value via the embedded settings
        $this->assertEquals('default', $settings->simpleSettings->getValue1());


        if ($settings->simpleSettings instanceof LazyObjectInterface) {
            $this->assertTrue($settings->simpleSettings->isLazyObjectInitialized());
        }
    }

    public function testGetEmbeddedCircular(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $this->assertInstanceOf(EmbedSettings::class, $settings);
        $this->assertInstanceOf(CircularEmbedSettings::class, $settings->circularSettings);

        //The embedded settings should be identical to the ones we get via the settings manager
        $this->assertSame($settings->circularSettings, $this->service->get(CircularEmbedSettings::class));

        //Circular references should be resolved
        $this->assertSame($settings, $settings->circularSettings->embeddedSettings);

        //Test if we can retrieve the value via the embedded settings
        $this->assertEquals('default', $settings->circularSettings->embeddedSettings->simpleSettings->getValue1());
    }

    public function testSaveCascadeFalse(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $settings->simpleSettings->setValue1('changed');
        $this->service->save($settings, false);

        //If the cascade is false, the embedded settings should not be saved and the default values reloaded
        $this->service->reload(SimpleSettings::class);
        $this->assertEquals('default', $this->service->get(SimpleSettings::class)->getValue1());
    }

    public function testSaveCascadeTrue(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);

        $settings->simpleSettings->setValue1('changed');
        $this->service->save($settings, true);

        $this->service->reload(SimpleSettings::class);
        $this->assertEquals('changed', $this->service->get(SimpleSettings::class)->getValue1());
    }

    public function testReloadCascadeFalse(): void
    {
        $settings = $this->service->get(EmbedSettings::class);
        $settings->simpleSettings->setValue1('changed');

        $this->service->reload($settings, false);

        //If the cascade is false, the embedded settings should not be reloaded and the value should be the same as before
        $this->assertEquals('changed', $settings->simpleSettings->getValue1());
    }

    public function testReloadCascadeTrue(): void
    {
        $settings = $this->service->get(EmbedSettings::class);
        $settings->simpleSettings->setValue1('changed');

        $this->service->reload($settings, true);

        //If the cascade is true, the embedded settings should be reloaded and the value should be the default value
        $this->assertEquals('default', $settings->simpleSettings->getValue1());
    }

    public function testIsEnvVarOverwritten(): void
    {
        $settings = $this->service->get(SimpleSettings::class);
        //Env vars should not be overwritten, if no env var is set
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, 'value1'));
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(SimpleSettings::class, 'value2')));
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(SimpleSettings::class, 'value3')));

        //The same goes for EnvVar parameters, as long as the env var is not set
        $settings = $this->service->get(EnvVarSettings::class);
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, 'value1'));
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(EnvVarSettings::class, 'value2')));
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(EnvVarSettings::class, 'value3')));
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, 'value4'));

        //Define some env vars
        $_ENV['ENV_VALUE1'] = "should not be applied";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";

        //The INITIAL value means not overwritten by an env var
        $this->assertFalse($this->service->isEnvVarOverwritten($settings, 'value1'));
        //The OVERRIDE modes must be marked as overwritten
        $this->assertTrue($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(EnvVarSettings::class, 'value2')));
        $this->assertTrue($this->service->isEnvVarOverwritten($settings, new \ReflectionProperty(EnvVarSettings::class, 'value3')));
        //The OVERRIDE_PERSIST mode must be marked as overwritten
        $this->assertTrue($this->service->isEnvVarOverwritten($settings, 'value4'));

        //Unset the env vars to prevent side effects
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);
    }

    public function testEnvVarHandling(): void
    {
        //Define some env vars
        $_ENV['ENV_VALUE1'] = "new initial value";
        $_ENV['ENV_VALUE2'] = 'true';
        $_ENV['ENV_VALUE3'] = 'dont matter';
        $_ENV['ENV_VALUE4'] = "1";

        /** @var EnvVarSettings $settings */
        $settings = $this->service->get(EnvVarSettings::class);
        //With the env vars in place, the values should be set when retrieving a new instance (without having the values in memory yet)
        $this->assertSame('new initial value', $settings->value1);
        $this->assertSame(true, $settings->value2);
        $this->assertSame(123.4, $settings->value3);
        $this->assertSame(true, $settings->value4);

        //Try to change some values
        $settings->value1 = 'changed';
        $settings->value2 = false;
        $settings->value3 = 432.1;
        $settings->value4 = null;

        //Persist the values to memory
        $this->service->save($settings);

        //If we load the settings from storage, the overwrite values should be like in the env vars, and the INITIAL values should be our set value
        $this->service->reload($settings);
        $this->assertSame('changed', $settings->value1);   //INITIAL
        $this->assertSame(true, $settings->value2); //Overwritten by env var
        $this->assertSame(123.4, $settings->value3); //Overwritten by env var
        $this->assertSame(true, $settings->value4); //Overwritten by env var

        //If we reset the values to default, the env vars should be applied againg (even the INITIAL ones)
        $settings->value3 = 432.1;
        $this->service->resetToDefaultValues($settings);
        $this->assertSame('new initial value', $settings->value1);   //Overwritten by env var
        $this->assertSame(true, $settings->value2); //Overwritten by env var
        $this->assertSame(123.4, $settings->value3); //Overwritten by env var
        $this->assertSame(true, $settings->value4); //Overwritten by env var

        //If we now unset the env vars and reload the settings, only the INITIAL and OVERRIDE_PERSIST values should be set,
        //as the OVERWRITE values were not persisted
        unset($_ENV['ENV_VALUE1'], $_ENV['ENV_VALUE2'], $_ENV['ENV_VALUE3'], $_ENV['ENV_VALUE4']);

        //Clear the internal envCache of the container to ensure that the env vars are reloaded
        //This is pretty hacky, but the only way to ensure that the env vars are reloaded
        $container = self::$kernel->getContainer(); //We can not use the getContainer method, as it returns a different container instance
        PropertyAccessHelper::setProperty( $container, 'envCache', []);


        $this->service->reload($settings);
        $this->assertSame('changed', $settings->value1);   //From memory
        $this->assertSame(false, $settings->value2); //Default value
        $this->assertSame(0.0, $settings->value3); //Default value
        $this->assertSame(null, $settings->value4); //The envVar value which was persisted

    }

    public function testCreateTemporaryCopy(): void
    {
        $settings = $this->service->get(SimpleSettings::class);
        $settings->setValue1('changed');

        $copy = $this->service->createTemporaryCopy($settings);
        //The copy must be a new instance
        $this->assertNotSame($settings, $copy);

        //We can create copy also by passing a class name
        $copy2 = $this->service->createTemporaryCopy(SimpleSettings::class);
        $this->assertInstanceOf(SimpleSettings::class, $copy2);
        $this->assertNotSame($settings, $copy2);
        //And it must also not be the same as the first copy
        $this->assertNotSame($copy, $copy2);
    }

    public function testMergeTemporaryCopyWithNoErrors(): void
    {
        /** @var ValidatableSettings $settings */
        $settings = $this->service->get(ValidatableSettings::class);
        /** @var ValidatableSettings $copy */
        $copy = $this->service->createTemporaryCopy($settings);

        //Change the value of the copy
        $copy->value1 = 'changed';
        $copy->value2 = 10;

        //Merge the copy into the settings
        $this->service->mergeTemporaryCopy($copy);

        //The values of the settings must be the same as the copy
        $this->assertEquals('changed', $settings->value1);
        $this->assertEquals(10, $settings->value2);
    }

    public function testMergeTemporaryCopyWithErrors(): void
    {
        /** @var ValidatableSettings $settings */
        $settings = $this->service->get(ValidatableSettings::class);
        /** @var ValidatableSettings $copy */
        $copy = $this->service->createTemporaryCopy($settings);

        //Change the value of the copy
        $copy->value1 = '';
        $copy->value2 = -10;

        //The copy is invalid, therefore throw an exception
        $this->expectException(\Jbtronics\SettingsBundle\Exception\SettingsNotValidException::class);

        //Merge the copy into the settings
        $this->service->mergeTemporaryCopy($copy);
    }

    public function testMergeTemporaryCopyWithDeepErrors(): void
    {
        /** @var EmbedSettings $settings */
        $settings = $this->service->get(EmbedSettings::class);
        /** @var EmbedSettings $copy */
        $copy = $this->service->createTemporaryCopy($settings);

        //Change the value deep inside of the copy and make it invalid
        $copy->circularSettings->validatableSettings->value1 = '';
        $copy->circularSettings->validatableSettings->value2 = -10;

        //The copy is invalid, therefore throw an exception
        $this->expectException(\Jbtronics\SettingsBundle\Exception\SettingsNotValidException::class);

        //Merge the copy into the settings
        $this->service->mergeTemporaryCopy($copy);
    }
}
