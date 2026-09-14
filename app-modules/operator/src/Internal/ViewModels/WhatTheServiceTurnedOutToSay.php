<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What reading one service's tail produced, flattened for a template.
 *
 * The sibling of {@see WhatStoppedTurnedOutToBe} and written the same way:
 * {@see \Modules\Operator\Internal\Presenters\HowAScrollbackReads} folds the
 * answer once and the template reads fields, because Blade has no `either()`
 * and cannot be given one.
 *
 * **Three states, and the silent service is one of them.** A service that has
 * said nothing in the lines that were asked for is running quietly; a session
 * that has ended is `N1-R44`'s screen; an obstacle is `N1-R10`'s. Folding the
 * first two together would have a signed-out phone report a quiet service,
 * which is the collapse {@see \Modules\Kernel\Api\WhatWasSaid} refuses one
 * layer up and this one must not rebuild.
 */
final readonly class WhatTheServiceTurnedOutToSay
{
    /**
     * @param bool                  $isSignedIn whether this device still holds a session for the stack
     * @param string                $met        the key for what stood in the way, or empty where nothing did
     * @param string                $remedy     the key for what to do about it, or empty where nothing did
     * @param list<WhatOneLineSays> $lines      the lines to show, oldest first
     * @param int                   $arrived    how many came back before anything narrowed them
     * @param int                   $bound      how many were asked for (`N2-R10`)
     * @param bool                  $isAWindow  whether the view stops where it was told to
     * @param bool                  $isSearching whether a search is narrowing the lines
     */
    public function __construct(
        public bool $isSignedIn,
        public string $met,
        public string $remedy,
        public array $lines,
        public int $arrived,
        public int $bound,
        public bool $isAWindow,
        public bool $isSearching,
    ) {}
}
