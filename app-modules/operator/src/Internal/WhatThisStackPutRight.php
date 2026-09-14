<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatWasMended;

/**
 * What carrying out an agreement came to, flattened for a template.
 *
 * The sibling of {@see WhatTheStackWouldPutRight}, and separate from it for the
 * reason {@see \Modules\Kernel\Api\HowTheRepairIsGoing} is separate from
 * {@see \Modules\Kernel\Api\HowTheOfferIsGoing}: one is a listing of what a
 * machine *would* do and the other a record of what it *did*, and a type
 * holding either would be one a template has to ask which it is looking at.
 *
 * **The ended arm is the one that carries the weight here.** After an offer, a
 * job the stack has forgotten costs a second question. After an agreement it
 * means the operator does not know what happened to their machine — and *it
 * failed* is the one answer that is certainly wrong, because it may well have
 * worked. The remedy is to look at the machine's health, and never to agree
 * again: that would be asking a stack to repeat work nobody can confirm it did
 * not already do.
 *
 * Every field but the first defaults, and each factory says only what its own
 * state means — {@see \Modules\Kernel\Api\Size}'s rule, and the cure for the
 * eighteen unreadable arguments mutation testing found in the fold beside this
 * one.
 */
final readonly class WhatThisStackPutRight
{
    /**
     * @param bool                     $isWorking whether the stack is still carrying it out
     * @param bool                     $hasEnded  whether the stack has forgotten the job
     * @param list<WhatOneOutcomeSays> $outcomes  what became of each repair, in the stack's order
     * @param int                      $changed   how many of them changed anything on the machine
     * @param string                   $met       the key for what stood in the way, or empty
     * @param string                   $remedy    the key for what to do about it, or empty
     */
    private function __construct(
        public bool $isWorking = false,
        public bool $hasEnded = false,
        public array $outcomes = [],
        public int $changed = 0,
        public string $met = '',
        public string $remedy = '',
    ) {}

    /** The stack is still carrying out what it was agreed to. */
    public static function stillWorkingItOut(): self
    {
        return new self(isWorking: true);
    }

    /** It finished, and this is what became of each repair. */
    public static function these(WhatWasMended $mended): self
    {
        $rows = [];

        foreach ($mended as $one) {
            $rows[] = WhatOneOutcomeSays::in($one);
        }

        // The count comes off the collection rather than off the rows, so the
        // line between *something happened* and *nothing did* is drawn once —
        // by `WhatBecameOfIt` — and this screen cannot come to disagree with
        // another about whether a run did anything.
        return new self(outcomes: $rows, changed: $mended->changed());
    }

    /** The stack has no outcome for that job any more. */
    public static function ended(): self
    {
        return new self(hasEnded: true);
    }

    /** The machine could not be reached, and this is what the operator met. */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why->said(), remedy: $why->remedy());
    }
}
