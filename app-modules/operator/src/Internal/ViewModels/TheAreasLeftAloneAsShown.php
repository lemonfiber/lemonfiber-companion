<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Everything the operator told the stack to leave alone, flattened for a template. */
final readonly class TheAreasLeftAloneAsShown
{
    /**
     * @param HowTheReadingWent            $went    how asking went
     * @param string                       $said    the catalogue key for the line that sums the list up, counted by `$howMany`
     * @param int                          $howMany how many areas are declared, listed or not
     * @param list<AnAreaLeftAloneAsShown> $areas   every area the stack listed, in the order declared
     * @param int                          $more    how many more the stack left out of the list
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $said,
        public int $howMany,
        public array $areas,
        public int $more,
    ) {}
}
