<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What the core says when a check produced no verdict at all (`N2-R3`).
 *
 * Two of the five outcomes are not judgements: `unverified` is a check that
 * could not be established, and `skipped` is one whose prerequisite was absent
 * so it never applied. Neither carries a code, a severity or a standing,
 * because there is nothing to grade — so neither fits {@see WentWrong}, and
 * making that type's five fields nullable to hold them would put the app back
 * in the shape {@see WhatTheCheckSaid} was written to get out of.
 *
 * **Both carry a reason, and `unverified` carries a remedy.** Those are the
 * only words the operator gets for a row with no answer on it, and a screen
 * that dropped them would show a check that did not run and nothing about why
 * or what to do to get an answer — which is the gap `N2-R3` exists to close,
 * one outcome over from where it was first closed.
 *
 * **The two are held together rather than apart.** They differ in what produced
 * them and not in what a screen does with them: a sentence, and a list of
 * remedies that is empty for one of them. `WentWrong` already reads "the core
 * offered nothing" and "the core offered an empty list" as the same thing, for
 * the same reason — the difference is about the core, not about the operator.
 */
final readonly class CouldNotSay
{
    private function __construct(private string $reason, private Remedies $remedies) {}

    /**
     * The reason is refused when blank, exactly as {@see WentWrong} refuses a
     * blank meaning. A row saying a check did not run, with no sentence saying
     * why, is a dead end the operator cannot act on or search for — and the
     * outcome that exists so a check that could not run is never mistaken for
     * one that passed loses that distinction the moment it says nothing.
     */
    public static function of(string $reason, Remedies $remedies): self
    {
        $said = trim($reason);

        if ($said === '') {
            throw CheckGaveNoReason::forNotRunning();
        }

        return new self($said, $remedies);
    }

    /** Why there is no answer, in the core's words rather than the app's. */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * What to do to get an answer, where the core knew.
     *
     * Empty for a skipped check, which is the honest answer: a prerequisite
     * that was absent is a fact about the machine, not a thing the core is
     * asking anybody to go and fix.
     */
    public function remedies(): Remedies
    {
        return $this->remedies;
    }
}
