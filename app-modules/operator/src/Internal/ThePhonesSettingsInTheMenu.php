<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * The item every stack's menu ends on: the phone's own settings.
 *
 * The one menu item that leads out of the stack, so its way there is an
 * accessor of its own rather than a case beside the stack's items.
 */
final readonly class ThePhonesSettingsInTheMenu
{
    /** The catalogue key of the item's label. */
    public function said(): string
    {
        return 'navigation.menu.app_settings';
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return 'settings_applications';
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return 'gearshape.2';
    }

    /** Where it leads: the phone's settings. */
    public function appSettings(): string
    {
        return AScreenWithoutAStack::Settings->value;
    }
}
