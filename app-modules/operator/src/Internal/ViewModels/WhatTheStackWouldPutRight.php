<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack what it would put right came to, flattened for a template.
 *
 * Five states, and they are five because {@see \Modules\Kernel\Api\Mending} is
 * two questions: the asking can fail on its own, and so can the reading, and
 * the reading has three answers of its own. A screen that folded any pair of
 * them together would be a screen with a wrong sentence for a real situation.
 *
 * - **signed out** — no session, so nothing was asked.
 * - **still working it out** — the stack took the question on and has not
 *   finished. The remedy is to ask again, and it is the operator's to take.
 * - **offering** — the listing, which is what the screen is about.
 * - **the job ended** — the stack has no outcome for that handle any more. Not
 *   a fault and not an answer: start again.
 * - **met an obstacle** — the machine could not be reached at all.
 *
 * **Nothing carries the handle.** A template has no use for it, and a pending action is
 * emphatic that an action must not be presented as pending — a job name on the
 * glass is exactly that, dressed as a diagnostic. The screen holds it; this
 * does not.
 */
final readonly class WhatTheStackWouldPutRight
{
    /** @param list<WhatOneRepairSays> $repairs what it would put right, in the order offered, where it offered anything */
    public function __construct(
        public WhereTheWorkStands $stands,
        public array $repairs = [],
    ) {}
}
