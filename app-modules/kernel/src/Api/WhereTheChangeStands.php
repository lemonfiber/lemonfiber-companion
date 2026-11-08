<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A proposed change, read against what is in force, and where it stands.
 *
 * **The refusal is a separate field from the stance, and both are needed.**
 * `blocked` says nothing was written for a reason that is not *nobody has said
 * yes yet*; the refusal is that reason, in the core's words. A screen with the
 * stance alone can say a change did not happen and not why, which is the
 * shape of message an operator retries three times.
 *
 * **What it comes to travels with it.** What the change would disturb is
 * carried on a staged proposal as well as an applied one, so the yes is given to
 * something stated. What proving a replacement credential came to is not read:
 * this app offers to change no credential.
 */
final readonly class WhereTheChangeStands
{
    private function __construct(
        public ProposedChange $change,
        public Stance $stance,
        public WhatAChangeComesTo $comesTo,
        private string $why,
    ) {}

    /**
     * Where the stack says it stands, with nothing standing in the way.
     */
    public static function at(ProposedChange $change, Stance $stance, WhatAChangeComesTo $comesTo): self
    {
        return new self(change: $change, stance: $stance, comesTo: $comesTo, why: '');
    }

    /**
     * Blocked, and this is what the stack said about it.
     *
     * The stance is not taken as an argument: a refusal that arrived with any
     * other stance would be a shape the contract does not describe, and
     * accepting one here would let a screen show a reason beside the word
     * `applied`.
     */
    public static function blocked(ProposedChange $change, string $why, WhatAChangeComesTo $comesTo): self
    {
        return new self(change: $change, stance: Stance::Blocked, comesTo: $comesTo, why: $why);
    }

    /**
     * What the stack said stood in the way, or nothing where nothing did.
     *
     * A plain string rather than an arm, because unlike the folds elsewhere
     * here there is no second thing to do with it: every caller either shows
     * this sentence or shows nothing, and an `either()` would make both
     * branches say *print it*.
     */
    public function why(): string
    {
        return $this->why;
    }
}
