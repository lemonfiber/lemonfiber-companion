<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Stacks\Api\AStacksScreen;

/**
 * The item every stack's menu ends its stack's part on: what this phone
 * keeps of the stack, and taking it off the phone.
 */
final readonly class TheStacksSettingsInTheMenu
{
    /** The catalogue key of the item's label, which is also the page's title. */
    public function said(): string
    {
        return 'navigation.menu.stack_settings';
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return 'settings';
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return 'gearshape';
    }

    /** The screen it leads to, for whichever stack the menu is about. */
    public function screen(): AStacksScreen
    {
        return AStacksScreen::OnThisPhone;
    }
}
