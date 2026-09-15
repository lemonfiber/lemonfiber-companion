<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Undoing;
use Modules\Operator\Internal\ViewModels\WhatOneRepairSays;

/**
 * One repair a stack offered, turned into the fields a row reads.
 *
 * **All three fields are set together, from inside the one closure.** That is
 * what carries the requirement across the boundary rather than merely across
 * the type: a template holding this object cannot have got two of the three,
 * because there was no moment at which two of them existed without the third.
 */
final readonly class HowARepairReads
{
    /** Fold one repair into the fields a row needs. */
    public function in(Repair $repair): WhatOneRepairSays
    {
        return $repair->stated(
            fn(string $does, Effects $effects, Undoing $undoing): WhatOneRepairSays => new WhatOneRepairSays(
                does: $does,
                effects: $this->each($effects),
                undoing: $undoing->saidOnTheScreen(),
                // Read outside the closure's three, because it is not one of
                // them: `Repair::answers()` is published on its own precisely
                // because it is not something the operator reads. It is how a
                // row knows which finding it belongs under.
                answers: $repair->answers(),
            ),
        );
    }

    /**
     * Every consequence, as a list.
     *
     * Collected rather than handed over as the collection, because a template
     * iterating an `IteratorAggregate` is a template holding a domain type —
     * and `E2` has the screen read fields. Nothing is dropped on the way:
     * `Effects` refuses a blank one at construction, so a stack that sent four
     * consequences cannot render as three.
     *
     * @return list<string>
     */
    private function each(Effects $effects): array
    {
        $said = [];

        foreach ($effects as $effect) {
            $said[] = $effect;
        }

        return $said;
    }
}
