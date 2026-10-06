<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Modules\Stacks\Api\AStacksScreen;

use function sprintf;

/**
 * The four tabs of a member's bottom bar, in the order it draws them.
 *
 * A member finds their way by these alone, with no side menu. A tab's screen
 * is the root of what the phone draws for it, so it has no back button and
 * carries the bar; a screen opened over one hides the bar and has the
 * platform's way back. A member lands on Home.
 */
enum TheHouseholdsTabs: string
{
    case Home = 'home';
    case Search = 'search';
    case Requests = 'requests';
    case Profile = 'profile';

    /** The screen the tab opens. */
    public function screen(): AStacksScreen
    {
        return match ($this) {
            self::Home => AStacksScreen::Shelf,
            self::Search => AStacksScreen::Search,
            self::Requests => AStacksScreen::Owed,
            self::Profile => AStacksScreen::Profile,
        };
    }

    /** The catalogue key of the tab's label, which is also its screen's title. */
    public function said(): string
    {
        return sprintf('household.tabs.%s', $this->value);
    }

    /** The Material icon Android draws above the label. */
    public function glyph(): string
    {
        return match ($this) {
            self::Home => 'home',
            self::Search => 'search',
            self::Requests => 'playlist_add',
            self::Profile => 'person',
        };
    }

    /** The SF Symbol iOS draws above the label. */
    public function iosGlyph(): string
    {
        return match ($this) {
            self::Home => 'house',
            self::Search => 'magnifyingglass',
            self::Requests => 'text.badge.plus',
            self::Profile => 'person.crop.circle',
        };
    }
}
