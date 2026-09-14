<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\WhatBecameOfIt;

/**
 * What became of one repair, flattened for a template to read.
 *
 * {@see Mended} answers through one closure so a row cannot render two of its
 * three facts, and Blade cannot call a closure — so the fold happens once per
 * row here, which is {@see WhatOneRepairSays}' shape and the reason it exists.
 *
 * **It carries the repair's own row rather than restating one clause of it.**
 * The first draft took only what the repair *does* and dropped what else it
 * affected and whether it could be undone, on the ground that `N2-R4`'s three
 * statements are what somebody reads *before* agreeing. The analyser refused
 * the closure that ignored two of its three arguments, and it was right twice
 * over: an arm that ignores what it is handed is not reading the fold, and the
 * two clauses are not noise afterwards either. *Can this be undone* is exactly
 * the question an operator has once a repair has worked, and *downloads paused
 * while it moved* is what explains the half hour they just had.
 *
 * **What was left is a field of its own rather than part of a sentence.** A
 * repair that stopped and left nothing can be agreed to again without a
 * thought; one that left something cannot, and the difference is the whole of
 * what the operator needs. A sentence composed here would put it where no
 * translator can reach it (`L1`).
 */
final readonly class WhatOneOutcomeSays
{
    /**
     * @param WhatOneRepairSays $repair        what was agreed to, stated as it was before
     * @param string            $became        the key for what became of it
     * @param string            $left          what it left on the machine, or empty where it left nothing
     * @param bool              $worthAnotherGo whether agreeing again could produce a different answer
     */
    private function __construct(
        public WhatOneRepairSays $repair,
        public string $became,
        public string $left,
        public bool $worthAnotherGo = false,
    ) {}

    /** Fold one outcome into the fields a row needs. */
    public static function in(Mended $mended): self
    {
        return $mended->said(
            static fn(Repair $repair, WhatBecameOfIt $became, LeftBehind $left): self => $left->either(
                something: static fn(string $what): self => new self(
                    repair: WhatOneRepairSays::in($repair),
                    became: $became->saidOnTheScreen(),
                    left: $what,
                    worthAnotherGo: $became->worthAnotherGo(),
                ),
                nothing: static fn(): self => new self(
                    repair: WhatOneRepairSays::in($repair),
                    became: $became->saidOnTheScreen(),
                    left: '',
                    worthAnotherGo: $became->worthAnotherGo(),
                ),
            ),
        );
    }
}
