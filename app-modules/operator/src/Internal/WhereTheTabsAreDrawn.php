<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_any;

use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatThisServiceSaid;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Wayfinding\Api\TheTabs;

/**
 * Which of this surface's screens draws each tab, and which tab each screen is under.
 *
 * A tab also has screens of its own, opened from it: what one service is doing
 * and what it has been saying are Services'. The bar is drawn on a tab's
 * screens, with that tab marked, and on no other.
 */
final readonly class WhereTheTabsAreDrawn
{
    /** Whether the screen drawn by this class is one of the tabs. */
    public static function drawnBy(string $class): bool
    {
        return array_any(TheTabs::cases(), static fn(TheTabs $tab): bool => self::classOf($tab) === $class);
    }

    /** The tab a screen is drawn under, or none for a screen the menu opens. */
    public static function owning(string $class): ?TheTabs
    {
        return match ($class) {
            HowThisStackIs::class => TheTabs::Health,
            WhatThisStackRuns::class, WhatToDoWithThis::class, WhatThisServiceSaid::class => TheTabs::Services,
            HowCurrentThisStackIs::class => TheTabs::Updates,
            WhatWouldBePutRight::class => TheTabs::Repairs,
            default => null,
        };
    }

    /**
     * The class that draws the tab's screen.
     *
     * @return class-string
     */
    public static function classOf(TheTabs $tab): string
    {
        return match ($tab) {
            TheTabs::Health => HowThisStackIs::class,
            TheTabs::Services => WhatThisStackRuns::class,
            TheTabs::Updates => HowCurrentThisStackIs::class,
            TheTabs::Repairs => WhatWouldBePutRight::class,
        };
    }
}
