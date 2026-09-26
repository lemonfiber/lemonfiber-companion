<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of putting a copy back, flattened for a template.
 *
 * Its own model rather than fields on {@see WhatPuttingItBackWouldShow},
 * because a listing of what would happen and a report of what did are drawn
 * from different values, so one can never be drawn as the other.
 */
final readonly class HowPuttingItBackWent
{
    /**
     * @param HowTheReadingWent   $went       whether asking after it came back, and what stood in the way where it did not
     * @param bool                $isWorking  whether the stack is still putting it back
     * @param bool                $hasEnded   whether the stack has no outcome for it any more
     * @param ?AScopeAsShown      $scope      what it restored
     * @param ?string             $takenBy    the version of lemonfiber that took the copy
     * @param ?ARelocationAsShown $relocation where the data went instead of where it came from, and nothing where it went back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public bool $hasEnded,
        public ?AScopeAsShown $scope,
        public ?string $takenBy,
        public ?ARelocationAsShown $relocation,
    ) {}
}
