<?php

declare(strict_types=1);

namespace Jbtronics\SettingsBundle\Tests\Fixtures\Settings;

use Jbtronics\SettingsBundle\Settings\SettingsParameter;

/**
 * Parent class of InheritedSettings, whose parameters are partially redeclared in the child class.
 */
class InheritedSettingsParent
{
    #[SettingsParameter(label: 'parent')]
    protected string $redeclared = '';

    #[SettingsParameter(label: 'parent')]
    protected string $redeclaredWithoutAttribute = '';

    #[SettingsParameter(label: 'parent')]
    protected string $inherited = '';

    #[SettingsParameter(label: 'parent')]
    private string $parentPrivate = '';
}
