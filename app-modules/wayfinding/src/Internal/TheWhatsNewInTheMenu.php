<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use Modules\Wayfinding\Api\AScreenWithoutAStack;

/**
 * The item every stack's menu opens with after the way to another stack: what is new on every stack.
 *
 * A screen without a stack, because what is new is gathered across them and
 * narrowed by stack on the screen itself.
 */
final readonly class TheWhatsNewInTheMenu
{
    /** The catalogue key of the item's label, which is also the screen's title. */
    public function said(): string
    {
        return 'navigation.menu.whats_new';
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return 'new_releases';
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return 'newspaper';
    }

    /** Where the item goes. */
    public function goes(): string
    {
        return AScreenWithoutAStack::WhatsNew->value;
    }
}
