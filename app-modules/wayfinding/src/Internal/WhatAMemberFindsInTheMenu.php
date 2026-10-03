<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Internal;

use Modules\Stacks\Api\AStacksScreen;

use function sprintf;

/** The member's own screens, which a member's menu offers after the list of stacks. */
enum WhatAMemberFindsInTheMenu: string
{
    case Yours = 'yours';
    case Shelf = 'shelf';

    /** The catalogue key of the item's label, which is also its screen's title. */
    public function said(): string
    {
        return sprintf('household.%s', $this->value);
    }

    /** The screen the item opens. */
    public function screen(): AStacksScreen
    {
        return match ($this) {
            self::Yours => AStacksScreen::Owed,
            self::Shelf => AStacksScreen::Shelf,
        };
    }

    /** The Material icon Android draws beside the label. */
    public function glyph(): string
    {
        return match ($this) {
            self::Yours => 'playlist_add',
            self::Shelf => 'movie',
        };
    }

    /** The SF Symbol iOS draws beside the label. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::Yours => 'text.badge.plus',
            self::Shelf => 'film',
        };
    }
}
