<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/**
 * The health summary as a template draws it, including when there is none to draw.
 *
 * Every state carries every field, and a field with nothing to say is empty
 * rather than invented: a screen that has heard nothing yet has no count, no
 * worst thing and no affected items, and says that it is waiting.
 */
final readonly class WhatTheOneLineSays
{
    /**
     * @param string                      $said      the key for the one line
     * @param string                      $counted   the key for how many things it counts, or empty where it counts none
     * @param int                         $howMany   how many things want attention, as the core counted them
     * @param string                      $worst     the worst thing, named, or empty where the core named nothing
     * @param AgoAsShown                  $ago       when the summary shown was heard, or live where it is current
     * @param string                      $met       the key for what stopped the subscription, or empty
     * @param string                      $remedy    the key for what to do about that, or empty
     * @param bool                        $listening whether a subscription is open, which decides which cadence is said
     * @param list<AnAffectedItemAsShown> $affected  every thing counted as wrong, worst first
     */
    public function __construct(
        public string $said,
        public string $counted,
        public int $howMany,
        public string $worst,
        public AgoAsShown $ago,
        public string $met,
        public string $remedy,
        public bool $listening,
        public array $affected,
    ) {}
}
