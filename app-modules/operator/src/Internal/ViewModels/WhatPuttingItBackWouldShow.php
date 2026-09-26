<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What putting a copy back would do, flattened for a template.
 *
 * A rehearsal throughout: nothing here describes something that has happened,
 * and every field is empty where the stack did not list the copy.
 */
final readonly class WhatPuttingItBackWouldShow
{
    /**
     * @param HowTheReadingWent   $went       whether the listing came back, and what stood in the way where it did not
     * @param bool                $namesACopy whether the screen was opened on a copy at all
     * @param ?AScopeAsShown      $scope      what the copy covers
     * @param ?string             $takenBy    the version of lemonfiber that took it
     * @param ?string             $takenAt    when it was taken, as the stack stamped it
     * @param list<string>        $contents   what it holds, which is what putting it back would overwrite
     * @param bool                $isOlder    whether it comes from an older major version
     * @param ?ARelocationAsShown $relocation where its data would go instead of where it came from, and nothing where it goes back
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $namesACopy,
        public ?AScopeAsShown $scope,
        public ?string $takenBy,
        public ?string $takenAt,
        public array $contents,
        public bool $isOlder,
        public ?ARelocationAsShown $relocation,
    ) {}
}
