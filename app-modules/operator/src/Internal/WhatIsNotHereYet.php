<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function sprintf;

/**
 * The menu's items this version of the app does not have yet.
 *
 * What's new at the menu's top and the stack's settings at its foot are drawn
 * all the same, and each opens a screen that says it is not in this version
 * yet. The value is the segment of that screen's path, so the screen can say
 * which it is.
 */
enum WhatIsNotHereYet: string
{
    case WhatsNew = 'whats_new';
    case StackSettings = 'stack_settings';

    /** The catalogue key of the item's label, which is also the screen's title. */
    public function said(): string
    {
        return sprintf('navigation.menu.%s', $this->value);
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return match ($this) {
            self::WhatsNew => 'new_releases',
            self::StackSettings => 'settings',
        };
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::WhatsNew => 'newspaper',
            self::StackSettings => 'gearshape',
        };
    }

    /** Where the item goes: the screen that says it is not here yet. */
    public function goes(): string
    {
        return AScreenWithoutAStack::NotHereYet->saying($this);
    }
}
