<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use function array_any;

use Modules\Household\Internal\Screens\LookingForATitle;
use Modules\Household\Internal\Screens\WhatThisTitleIs;
use Modules\Household\Internal\Screens\WhatYouAreOwed;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Household\Internal\Screens\YourCornerOfTheHouse;
use Modules\Wayfinding\Api\TheHouseholdsTabs;

/**
 * Which of the member's screens each tab draws, and which tab a screen is under.
 *
 * A tab's screen is the root of what the phone draws for it, and carries the
 * bar. Every other member screen is opened over one and hides the bar.
 */
final readonly class WhereTheTabsAreDrawn
{
    /** Whether this screen is the one a tab draws. */
    public static function drawnBy(string $class): bool
    {
        return array_any(TheHouseholdsTabs::cases(), static fn(TheHouseholdsTabs $tab): bool => self::classOf($tab) === $class);
    }

    /** The tab this screen is drawn under, which the bar marks, or none. */
    public static function owning(string $class): ?TheHouseholdsTabs
    {
        return match ($class) {
            WhatYouCanWatch::class, WhatThisTitleIs::class => TheHouseholdsTabs::Home,
            LookingForATitle::class => TheHouseholdsTabs::Search,
            WhatYouAreOwed::class => TheHouseholdsTabs::Requests,
            YourCornerOfTheHouse::class => TheHouseholdsTabs::Profile,
            default => null,
        };
    }

    /** The screen a tab draws. */
    public static function classOf(TheHouseholdsTabs $tab): string
    {
        return match ($tab) {
            TheHouseholdsTabs::Home => WhatYouCanWatch::class,
            TheHouseholdsTabs::Search => LookingForATitle::class,
            TheHouseholdsTabs::Requests => WhatYouAreOwed::class,
            TheHouseholdsTabs::Profile => YourCornerOfTheHouse::class,
        };
    }
}
