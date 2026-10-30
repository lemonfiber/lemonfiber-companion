<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where running a check that disturbs has got to, flattened for a template. */
final readonly class TheRunTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands    $stands      where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null $refused   the stack's refusal, where it refused
     * @param bool                 $answered  whether the run finished and reported
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public bool $answered,
    ) {}
}
