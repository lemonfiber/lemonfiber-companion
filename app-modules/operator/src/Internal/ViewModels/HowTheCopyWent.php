<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What became of the copy asked for from this screen, flattened for a template.
 *
 * Every field the stack's report fills is empty until there is a report, so a
 * template cannot read what a copy removed off a copy still being taken.
 */
final readonly class HowTheCopyWent
{
    /**
     * @param WhereTheWorkStands  $stands         where the work stands, and whether the stack could be asked at all
     * @param bool               $wasRehearsed whether the report is of a rehearsal, which wrote nothing
     * @param ?AScopeAsShown     $scope        what it covers, where a copy was asked for
     * @param ?string            $tookSaid     the catalogue key for what it did, in the tense the report allows
     * @param list<string>       $pruned       the older copies it removed, by name
     * @param ?string            $prunedSaid   the catalogue key for how many it removed, counted on the names
     * @param ?APaceAsShown      $pace         what it came to against the budget
     * @param ?string            $holdsSaid    the catalogue key for whether it holds credentials
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public bool $wasRehearsed,
        public ?AScopeAsShown $scope,
        public ?string $tookSaid,
        public array $pruned,
        public ?string $prunedSaid,
        public ?APaceAsShown $pace,
        public ?string $holdsSaid,
    ) {}
}
