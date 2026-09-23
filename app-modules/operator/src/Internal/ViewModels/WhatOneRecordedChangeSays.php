<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One change, flattened for a template to read.
 *
 * {@see \Modules\Kernel\Api\Change} hands where it stops short over through a
 * fold and Blade has no way to call one, so
 * {@see \Modules\Operator\Internal\Presenters\HowARecordedChangeReads} folds it once
 * per row into this — the argument {@see WhatOneUnattendedCommandSays} makes.
 *
 * **Everything but the last two is always set**, because the value it comes
 * from refuses to be built without it; this must not undo that by defaulting a
 * field.
 *
 * **`$because` and `$instead` are empty on most rows, and the template must
 * branch on them rather than print them.** Only a change that stops short has a
 * reason, and only some of those a suggestion. A blank printed under *why it
 * stops short* reads as a limit nobody knows the reason for.
 */
final readonly class WhatOneRecordedChangeSays
{
    /**
     * @param string $did          what it did, in the operator's terms
     * @param string $operation    the operation that made it
     * @param string $target       what it was made to
     * @param string $reversalSaid the key for how far it goes back
     * @param int    $alongside    how many changes the operation made, this one among them
     * @param string $because      why putting it back stops short, or empty
     * @param string $instead      what to do instead, or empty
     */
    public function __construct(
        public string $did,
        public string $operation,
        public string $target,
        public string $reversalSaid,
        public int $alongside,
        public string $because,
        public string $instead,
    ) {}
}
