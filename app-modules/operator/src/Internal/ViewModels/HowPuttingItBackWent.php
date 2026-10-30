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
     * @param WhereTheWorkStands   $stands       where the work stands, and whether the stack could be asked at all
     * @param ?AScopeAsShown      $scope      what it restored
     * @param ?string             $takenBy    the version of lemonfiber that took the copy
     * @param ?ARelocationAsShown $relocation where the data went instead of where it came from, and nothing where it went back
     * @param ?ARefusalAsShown    $refused    why the stack did not finish putting it back, in its words, and nothing unless it refused
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?AScopeAsShown $scope,
        public ?string $takenBy,
        public ?ARelocationAsShown $relocation,
        public ?ARefusalAsShown $refused,
    ) {}
}
