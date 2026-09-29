<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_any;

use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;
use Modules\Operator\Internal\Screens\HowThisStackIs;
use Modules\Operator\Internal\Screens\WhatThisStackRuns;
use Modules\Operator\Internal\Screens\WhatWouldBePutRight;
use Modules\Stacks\Api\AStacksScreen;

/**
 * The four tabs of the bottom bar, in the order it draws them.
 *
 * A tab's screen is the root of what the phone draws for it, so it has no
 * back button; every other screen about a stack is opened on top of one.
 */
enum TheTabs: string
{
    case Health = 'health';
    case Services = 'services';
    case Updates = 'updates';
    case Repairs = 'repairs';

    /** Whether the screen drawn by this class is one of the tabs. */
    public static function drawnBy(string $class): bool
    {
        return array_any(self::cases(), fn(TheTabs $tab): bool => $tab->screenClass() === $class);
    }

    /** The screen the tab opens. */
    public function screen(): AStacksScreen
    {
        return match ($this) {
            self::Health => AStacksScreen::Health,
            self::Services => AStacksScreen::Services,
            self::Updates => AStacksScreen::Updates,
            self::Repairs => AStacksScreen::Repairs,
        };
    }

    /**
     * The class that draws the tab's screen.
     *
     * @return class-string
     */
    public function screenClass(): string
    {
        return match ($this) {
            self::Health => HowThisStackIs::class,
            self::Services => WhatThisStackRuns::class,
            self::Updates => HowCurrentThisStackIs::class,
            self::Repairs => WhatWouldBePutRight::class,
        };
    }
}
