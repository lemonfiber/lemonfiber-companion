<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Undoing;

/**
 * One repair a stack offered, flattened for a template to read.
 *
 * {@see Repair} publishes its three clauses through one closure rather than
 * three accessors, because `N2-R4`'s three statements are one requirement and
 * three getters are three chances to call two of them. Blade cannot call a
 * closure, so the fold happens once per row here — {@see WhatOneFindingSays}'
 * shape, and the reason that class exists.
 *
 * **All three fields are set together, from inside the one closure.** That is
 * what carries the requirement across the boundary rather than merely across
 * the type: a template holding this object cannot have got two of the three,
 * because there was no moment at which two of them existed without the third.
 */
final readonly class WhatOneRepairSays
{
    /**
     * @param string       $does      what the repair would do, in the stack's own words
     * @param list<string> $effects   what else it touches, which may be nothing
     * @param string       $undoing   the key for whether it can be taken back
     * @param string       $answers   which check it belongs under, for grouping it
     */
    private function __construct(
        public string $does,
        public array $effects,
        public string $undoing,
        public string $answers,
    ) {}

    /** Fold one repair into the fields a row needs. */
    public static function in(Repair $repair): self
    {
        return $repair->stated(
            static fn(string $does, Effects $effects, Undoing $undoing): self => new self(
                does: $does,
                effects: self::each($effects),
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
    private static function each(Effects $effects): array
    {
        $said = [];

        foreach ($effects as $effect) {
            $said[] = $effect;
        }

        return $said;
    }
}
