<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of putting a run back, flattened for a template.
 *
 * Its own model rather than fields on {@see WhatPuttingARunBackWouldShow},
 * because the record's rows and the stack's report are drawn from different
 * values, so one can never be drawn as the other.
 *
 * **`$left` is read first.** A report with anything in it did not put the
 * whole run back, and {@see $leftNothing} is the one field that says it did.
 *
 * **The tense is decided before the template.** A rehearsal and a run carried
 * out name the same changes, and the keys here are the only difference between
 * *went back* and *would go back*, so a template cannot say one in the other's.
 */
final readonly class HowPuttingARunBackWent
{
    /**
     * @param HowTheReadingWent           $went        whether asking after it came back, and what stood in the way where it did not
     * @param bool                        $isWorking   whether the stack is still putting it back
     * @param bool                        $hasEnded    whether the stack has no outcome for it any more
     * @param bool                        $hasReport   whether the stack reported what it came to
     * @param bool                        $rehearsed   whether the report only says what would go back
     * @param bool                        $leftNothing whether everything went back, which is only where nothing was left
     * @param string                      $headline    the key for the first thing said of the report: whether all of it went back, or would, in the report's tense
     * @param string                      $reversedSaid the key heading what went back, or would, in the report's tense
     * @param string                      $noneReversed the key for nothing having gone back, or nothing that would, in the report's tense
     * @param list<AChangeGoneBackAsShown> $reversed    what went back, or would, in the stack's order
     * @param list<AChangeAndWhyAsShown>  $left        what did not go back, or could not be promised, each with why
     * @param list<AChangeAndWhyAsShown>  $noted       what going back means beyond the changes themselves
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public bool $hasReport,
        public bool $rehearsed,
        public bool $leftNothing,
        public string $headline,
        public string $reversedSaid,
        public string $noneReversed,
        public array $reversed,
        public array $left,
        public array $noted,
    ) {}
}
