<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Fixtures\Settings;

use Jbtronics\SettingsBundle\Settings\Settings;
use Jbtronics\SettingsBundle\Settings\SettingsParameter;

/**
 * A settings class, which redeclares some of the parameters of its parent class.
 */
#[Settings]
class InheritedSettings extends InheritedSettingsParent
{
    #[SettingsParameter(label: 'child')]
    protected string $redeclared = '';

    //Redeclared without attribute, so it should not be a parameter anymore
    protected string $redeclaredWithoutAttribute = '';
}
